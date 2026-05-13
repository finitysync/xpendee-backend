<?php

namespace App\Http\Controllers\API\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SettingsController extends Controller
{
    /**
     * GET /api/settings
     * Returns the authenticated tenant's settings.
     */
    public function show(Request $request)
    {
        $tenant = $request->user();

        return response()->json([
            'success' => true,
            'data'    => $this->formatSettings($tenant),
        ]);
    }

    /**
     * POST /api/settings
     * Partially updates the tenant's settings.
     */
    public function update(Request $request)
    {
        $tenant = $request->user();

        $validated = $request->validate([
            // General / Branding
            'app_name'             => 'sometimes|string|max:100',
            'app_tagline'          => 'sometimes|string|max:255|nullable',
            'company_name'         => 'sometimes|string|max:255|nullable',
            'logo'                 => 'sometimes|string|nullable',
            'primary_color'        => 'sometimes|string|max:20|nullable',
            'address'              => 'sometimes|string|nullable',

            // Invoice defaults
            'invoice_prefix'       => 'sometimes|string|max:20|nullable',
            'invoice_tax_percent'  => 'sometimes|numeric|min:0|max:100|nullable',
            'invoice_due_days'     => 'sometimes|integer|min:0|nullable',

            // Payment & Notes
            'payment_details'      => 'sometimes|string|nullable',
            'default_notes'        => 'sometimes|string|nullable',
            'default_contract_description' => 'sometimes|string|nullable',
            'contract_terms'       => 'sometimes|string|nullable', // JSON string

            // SMTP
            'smtp_host'            => 'sometimes|string|max:255|nullable',
            'smtp_port'            => 'sometimes|integer|nullable',
            'smtp_user'            => 'sometimes|string|max:255|nullable',
            'smtp_pass'            => 'sometimes|string|nullable',
            'smtp_from_name'       => 'sometimes|string|max:100|nullable',
            'smtp_from_email'      => 'sometimes|email|max:255|nullable',

            // Business info (stored as flattened columns or JSON)
            'business_name'        => 'sometimes|string|max:255|nullable',
            'business_email'       => 'sometimes|email|max:255|nullable',
            'business_phone'       => 'sometimes|string|max:50|nullable',
            'business_address'     => 'sometimes|string|nullable',
            'business_website'     => 'sometimes|string|max:255|nullable',
        ]);

        // Map frontend payload keys to Tenant fillable columns
        $map = [
            'app_name'                     => 'app_name',
            'company_name'                 => 'company_name',
            'logo'                         => 'logo',
            'primary_color'                => 'primary_color',
            'address'                      => 'address',
            'invoice_prefix'               => 'invoice_prefix',
            'invoice_tax_percent'          => 'invoice_tax_percent',
            'invoice_due_days'             => 'invoice_due_days',
            'smtp_host'                    => 'smtp_host',
            'smtp_port'                    => 'smtp_port',
            'smtp_user'                    => 'smtp_user',
            'smtp_from_name'               => 'smtp_from_name',
            'smtp_from_email'              => 'smtp_from_email',
        ];

        $updateData = [];
        foreach ($map as $input => $column) {
            if (array_key_exists($input, $validated)) {
                $updateData[$column] = $validated[$input];
            }
        }

        // SMTP password — only update if explicitly provided (non-empty)
        if (!empty($validated['smtp_pass'])) {
            $updateData['smtp_pass'] = $validated['smtp_pass'];
        }

        // Extra JSON columns stored as text on tenant row (extend Tenant table if needed)
        // For now store them via the existing columns where possible
        if (array_key_exists('business_name', $validated)) {
            $updateData['company_name'] = $validated['business_name'];
        }

        $tenant->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully.',
            'data'    => $this->formatSettings($tenant->fresh()),
        ]);
    }

    /**
     * Format tenant record into the shape the frontend AppSettings expects.
     */
    private function formatSettings($tenant): array
    {
        return [
            'appName'                    => $tenant->app_name ?? 'Xpendee',
            'appTagline'                 => '',
            'logo'                       => $tenant->logo ?? '',
            'favicon'                    => '',
            'brandColor'                 => $tenant->primary_color ?? '#4169E1',
            'dateFormat'                 => 'DD/MM/YYYY',
            'invoicePrefix'              => $tenant->invoice_prefix ?? 'INV-',
            'businessInfo'               => [
                'name'    => $tenant->company_name ?? $tenant->name ?? '',
                'email'   => $tenant->email ?? '',
                'phone'   => $tenant->address ?? '',   // placeholder until phone col added
                'address' => $tenant->address ?? '',
                'website' => '',
            ],
            'paymentDetails'             => '',
            'defaultNotes'               => '',
            'defaultContractDescription' => '',
            'contractTerms'              => [],
            'smtp'                       => [
                'host'      => $tenant->smtp_host ?? '',
                'port'      => $tenant->smtp_port ?? 587,
                'email'     => $tenant->smtp_user ?? '',
                'password'  => '',  // never expose password to frontend
                'fromName'  => $tenant->smtp_from_name ?? '',
                'fromEmail' => $tenant->smtp_from_email ?? '',
            ],
            'lastEmailSent' => null,
            'emailTemplates' => $this->defaultEmailTemplates(),
        ];
    }

    /**
     * Default email templates (can be moved to DB later).
     */
    private function defaultEmailTemplates(): array
    {
        $empty = ['subject' => '', 'body' => '', 'enabled' => true];
        return [
            'invoiceCreated'       => $empty,
            'invoiceRevised'       => $empty,
            'manualSend'           => $empty,
            'paymentReceived'      => $empty,
            'paymentPartial'       => $empty,
            'paymentReminder'      => $empty,
            'invoiceCancelled'     => $empty,
            'lateFeeAdded'         => $empty,
            'contractSignedClient' => $empty,
            'contractSignedAdmin'  => $empty,
            'contractOtp'          => $empty,
            'contractManualSend'   => $empty,
            'contractExpiredAdmin' => $empty,
        ];
    }
}
