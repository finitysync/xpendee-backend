<?php

namespace App\Http\Controllers\API\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    /**
     * GET /api/clients
     * List all clients for the authenticated tenant.
     */
    public function index(Request $request)
    {
        $tenantId = $request->user()->id;

        $clients = Client::forTenant($tenantId)
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $clients,
        ]);
    }

    /**
     * POST /api/clients
     * Create a new client for the authenticated tenant.
     */
    public function store(Request $request)
    {
        $tenantId = $request->user()->id;

        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => [
                'nullable',
                'email',
                'max:255',
                // Email must be unique per tenant (not globally)
                Rule::unique('clients')->where(fn ($query) =>
                    $query->where('tenant_id', $tenantId)->whereNull('deleted_at')
                ),
            ],
            'phone'   => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'notes'   => 'nullable|string',
        ]);

        $client = Client::create([
            'tenant_id' => $tenantId,
            ...$validated,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Client created successfully.',
            'data'    => $client,
        ], 201);
    }

    /**
     * GET /api/clients/{id}
     * Get a single client (must belong to authenticated tenant).
     */
    public function show(Request $request, int $id)
    {
        $tenantId = $request->user()->id;

        $client = Client::forTenant($tenantId)->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $client,
        ]);
    }

    /**
     * PUT /api/clients/{id}
     * Update a client (must belong to authenticated tenant).
     */
    public function update(Request $request, int $id)
    {
        $tenantId = $request->user()->id;

        $client = Client::forTenant($tenantId)->findOrFail($id);

        $validated = $request->validate([
            'name'    => 'sometimes|required|string|max:255',
            'email'   => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('clients')
                    ->where(fn ($query) =>
                        $query->where('tenant_id', $tenantId)->whereNull('deleted_at')
                    )
                    ->ignore($client->id),
            ],
            'phone'   => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'notes'   => 'nullable|string',
        ]);

        $client->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Client updated successfully.',
            'data'    => $client->fresh(),
        ]);
    }

    /**
     * DELETE /api/clients/{id}
     * Soft-delete a client (must belong to authenticated tenant).
     */
    public function destroy(Request $request, int $id)
    {
        $tenantId = $request->user()->id;

        $client = Client::forTenant($tenantId)->findOrFail($id);
        $client->delete();

        return response()->json([
            'success' => true,
            'message' => 'Client deleted successfully.',
        ]);
    }
}
