<?php

namespace App\Http\Controllers\API\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\ContractSignedMail;
use App\Models\Contract;
use App\Models\EmailHistory;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class PublicContractController extends Controller
{
    /**
     * GET /api/public/contract/{token}
     * View contract details for signing — no auth required.
     */
    public function show(string $token)
    {
        $contract = Contract::with(['client', 'tenant'])
            ->where('share_token', $token)
            ->whereIn('status', ['sent', 'signed'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data'    => [
                'id'           => (string) $contract->id,
                'title'        => $contract->title,
                'body'         => $contract->body,
                'status'       => $contract->status,
                'signer_name'  => $contract->signer_name,
                'signer_email' => $contract->signer_email,
                'signed_at'    => $contract->signed_at?->toIso8601String(),
                'tenant' => [
                    'name'    => $contract->tenant?->app_name ?? $contract->tenant?->name,
                    'email'   => $contract->tenant?->email,
                    'logo'    => $contract->tenant?->logo,
                ],
            ],
        ]);
    }

    /**
     * POST /api/public/contract/{token}/request-otp
     * Send a 6-digit OTP to the signer's email. No auth required.
     */
    public function requestOtp(Request $request, string $token)
    {
        $contract = Contract::with('tenant')
            ->where('share_token', $token)
            ->where('status', 'sent')
            ->firstOrFail();

        $validated = $request->validate([
            'email' => 'required|email',
            'name'  => 'nullable|string|max:255',
        ]);

        // Update signer info if provided
        $contract->update([
            'signer_email' => $validated['email'],
            'signer_name'  => $validated['name'] ?? $contract->signer_name,
        ]);

        $plainOtp = $contract->generateOtp();

        // Send OTP via email
        Mail::to($validated['email'])->send(
            new \App\Mail\OtpMail($plainOtp, $contract->title, $contract->tenant)
        );

        // Log to history
        EmailHistory::log(
            tenantId:  $contract->tenant_id,
            to:        $validated['email'],
            subject:   'Your Signing OTP Code',
            type:      'otp',
            relatedId: $contract->id
        );

        return response()->json([
            'success' => true,
            'message' => "OTP sent to {$validated['email']}. Valid for 10 minutes.",
        ]);
    }

    /**
     * POST /api/public/contract/{token}/sign
     * Verify OTP + save signature + generate signed PDF + email both parties.
     */
    public function sign(Request $request, string $token)
    {
        $contract = Contract::with(['client', 'tenant'])
            ->where('share_token', $token)
            ->where('status', 'sent')
            ->firstOrFail();

        $validated = $request->validate([
            'otp'            => 'required|string|size:6',
            'signature_data' => 'required|string',   // base64 image data
            'signer_name'    => 'nullable|string|max:255',
        ]);

        // Verify OTP
        if (!$contract->verifyOtp($validated['otp'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP. Please request a new one.',
            ], 422);
        }

        // Save signature + mark as signed
        $contract->update([
            'signature_data' => $validated['signature_data'],
            'signer_name'    => $validated['signer_name'] ?? $contract->signer_name,
            'status'         => 'signed',
            'signed_at'      => now(),
            'otp'            => null,
            'otp_expires_at' => null,
        ]);

        $contract->refresh()->load(['client', 'tenant']);

        // Generate signed PDF
        $pdf = Pdf::loadView('pdfs.contract', [
            'contract' => $contract,
            'tenant'   => $contract->tenant,
        ]);
        $pdfContent = $pdf->output();

        // Email tenant (agency)
        if ($contract->tenant?->email) {
            Mail::to($contract->tenant->email)->send(new ContractSignedMail(
                contract:   $contract,
                tenant:     $contract->tenant,
                pdf:        $pdfContent,
                recipient:  'agency',
            ));
        }

        // Email client/signer
        if ($contract->signer_email) {
            Mail::to($contract->signer_email)->send(new ContractSignedMail(
                contract:   $contract,
                tenant:     $contract->tenant,
                pdf:        $pdfContent,
                recipient:  'client',
            ));
        }

        return response()->json([
            'success' => true,
            'message' => 'Contract signed successfully! A copy has been emailed to you.',
        ]);
    }
}
