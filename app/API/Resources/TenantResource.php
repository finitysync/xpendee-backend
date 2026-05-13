<?php

namespace App\API\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'company_name' => $this->company_name,
            'logo' => $this->logo,
            'address' => $this->address,
            'app_name' => $this->app_name,
            'primary_color' => $this->primary_color,
            'status' => $this->status,
            'plan' => $this->plan,
            'plan_duration' => $this->plan_duration,
            'plan_expires_at' => $this->plan_expires_at,
            'trial_ends_at' => $this->trial_ends_at,
            'is_trial_active' => $this->is_trial_active,
            'is_active' => $this->is_active,
            'invoice_prefix' => $this->invoice_prefix,
            'invoice_tax_percent' => $this->invoice_tax_percent,
            'invoice_due_days' => $this->invoice_due_days,
            'smtp_host' => $this->smtp_host,
            'smtp_port' => $this->smtp_port,
            'smtp_user' => $this->smtp_user,
            'smtp_from_name' => $this->smtp_from_name,
            'smtp_from_email' => $this->smtp_from_email,
            'created_at' => $this->created_at,
        ];
    }
}
