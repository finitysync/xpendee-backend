<?php

namespace App\Http\Controllers\API\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\ContractSigningMail;
use App\Models\Contract;
use App\Models\EmailHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContractController extends Controller
{
    /**
     * GET /api/contracts
     */
    public function index(Request $request)
    {
        $contracts = Contract::forTenant($request->user()->id)
            ->with('client')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($c) => $this->format($c));

        return response()->json(['success' => true, 'data' => $contracts]);
    }

    /**
     * POST /api/contracts
     */
    public function store(Request $request)
    {
        $tenantId = $request->user()->id;

        $validated = $request->validate([
            'title'        => 'required|string|max:255',
            'body'         => 'required|string',
            'client_id'    => 'nullable|integer|exists:clients,id',
            'status'       => 'nullable|in:draft,sent,signed,cancelled',
            'signer_name'  => 'nullable|string|max:255',
            'signer_email' => 'nullable|email|max:255',
        ]);

        $contract = Contract::create([
            'tenant_id'    => $tenantId,
            'client_id'    => $validated['client_id'] ?? null,
            'title'        => $validated['title'],
            'body'         => $validated['body'],
            'status'       => $validated['status'] ?? 'draft',
            'signer_name'  => $validated['signer_name']  ?? null,
            'signer_email' => $validated['signer_email'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Contract created.',
            'data'    => $this->format($contract->load('client')),
        ], 201);
    }

    /**
     * GET /api/contracts/{id}
     */
    public function show(Request $request, int $id)
    {
        $contract = Contract::forTenant($request->user()->id)
            ->with('client')
            ->findOrFail($id);

        return response()->json(['success' => true, 'data' => $this->format($contract)]);
    }

    /**
     * PUT /api/contracts/{id}
     */
    public function update(Request $request, int $id)
    {
        $contract = Contract::forTenant($request->user()->id)->findOrFail($id);

        $validated = $request->validate([
            'title'        => 'sometimes|required|string|max:255',
            'body'         => 'sometimes|required|string',
            'client_id'    => 'nullable|integer|exists:clients,id',
            'status'       => 'nullable|in:draft,sent,signed,cancelled',
            'signer_name'  => 'nullable|string|max:255',
            'signer_email' => 'nullable|email|max:255',
        ]);

        $contract->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Contract updated.',
            'data'    => $this->format($contract->fresh()->load('client')),
        ]);
    }

    /**
     * DELETE /api/contracts/{id}
     */
    public function destroy(Request $request, int $id)
    {
        Contract::forTenant($request->user()->id)->findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'Contract deleted.']);
    }

    /**
     * POST /api/contracts/{id}/send
     * Generate share token + send signing link to client/email.
     */
    public function send(Request $request, int $id)
    {
        $tenant   = $request->user();
        $contract = Contract::forTenant($tenant->id)->with('client')->findOrFail($id);

        $validated = $request->validate([
            'to'      => 'nullable|email',
            'subject' => 'nullable|string',
            'body'    => 'nullable|string',
        ]);

        // Generate share token if not already set
        if (!$contract->share_token) {
            $contract->update([
                'share_token' => Contract::generateShareToken(),
                'status'      => 'sent',
            ]);
        } else {
            $contract->update(['status' => 'sent']);
        }

        $toEmail = $validated['to']
            ?? $contract->signer_email
            ?? $contract->client?->email;

        if ($toEmail) {
            $signingUrl = config('app.frontend_url', 'http://localhost:3000')
                . '/sign-contract/' . $contract->share_token;

            Mail::to($toEmail)->send(new ContractSigningMail(
                contract:   $contract->fresh(),
                tenant:     $tenant,
                signingUrl: $signingUrl,
                subject:    $validated['subject'] ?? "Please sign: {$contract->title}",
                message:    $validated['body'] ?? '',
            ));

            // Log to history
            EmailHistory::log(
                tenantId:  $tenant->id,
                to:        $toEmail,
                subject:   $validated['subject'] ?? "Please sign: {$contract->title}",
                type:      'contract',
                relatedId: $contract->id
            );
        }

        return response()->json([
            'success'     => true,
            'message'     => $toEmail ? "Contract sent to {$toEmail}." : 'Share token generated.',
            'data'        => $this->format($contract->fresh()),
            'signing_url' => config('app.frontend_url', 'http://localhost:3000')
                . '/sign-contract/' . $contract->share_token,
        ]);
    }

    // ─── Format ───────────────────────────────────────────────────────────────

    private function format(Contract $c): array
    {
        return [
            'id'            => (string) $c->id,
            'tenant_id'     => $c->tenant_id,
            'client_id'     => $c->client_id ? (string) $c->client_id : null,
            'title'         => $c->title,
            'body'          => $c->body,
            'status'        => $c->status,
            'share_token'   => $c->share_token,
            'signed_at'     => $c->signed_at?->toIso8601String(),
            'signer_name'   => $c->signer_name,
            'signer_email'  => $c->signer_email,
            'has_signature' => !empty($c->signature_data),
            'client'        => $c->client ? [
                'id'    => (string) $c->client->id,
                'name'  => $c->client->name,
                'email' => $c->client->email,
            ] : null,
            // Frontend compat aliases
            'clientName'    => $c->client?->name ?? $c->signer_name ?? '',
            'clientEmail'   => $c->client?->email ?? $c->signer_email ?? '',
            'createdAt'     => $c->created_at ? $c->created_at->getTimestamp() * 1000 : null,
            'created_at'    => $c->created_at?->toIso8601String(),
        ];
    }
}
