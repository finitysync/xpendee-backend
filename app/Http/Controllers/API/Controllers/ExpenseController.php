<?php

namespace App\Http\Controllers\API\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExpenseController extends Controller
{
    // Default categories — returned on every index + summary response
    private const DEFAULT_CATEGORIES = [
        'Software/Tools',
        'Salaries',
        'Office/Rent',
        'Marketing & Ads',
        'Hardware/Equipment',
        'Miscellaneous',
    ];

    /**
     * GET /api/expenses
     * List all expenses for the authenticated tenant.
     */
    public function index(Request $request)
    {
        $tenantId = $request->user()->id;

        $query = Expense::forTenant($tenantId)->orderByDesc('date');

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('from')) {
            $query->whereDate('date', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('date', '<=', $request->to);
        }

        $expenses = $query->get()->map(fn ($e) => $this->format($e));

        return response()->json([
            'success'    => true,
            'data'       => $expenses,
            'categories' => self::DEFAULT_CATEGORIES,
        ]);
    }

    /**
     * POST /api/expenses
     * Create expense — supports multipart (receipt upload) or JSON.
     */
    public function store(Request $request)
    {
        $tenantId = $request->user()->id;

        $validated = $request->validate([
            'title'    => 'required|string|max:255',
            'amount'   => 'required|numeric|min:0',
            'category' => 'required|string|max:100',
            'date'     => 'required|date',
            'notes'    => 'nullable|string',
            'receipt'  => 'nullable|file|mimes:jpg,jpeg,png,pdf,webp|max:5120',
        ]);

        $receiptPath = null;
        if ($request->hasFile('receipt') && $request->file('receipt')->isValid()) {
            $receiptPath = $request->file('receipt')->store(
                "receipts/{$tenantId}",
                'public'
            );
        }

        $expense = Expense::create([
            'tenant_id'    => $tenantId,
            'title'        => $validated['title'],
            'amount'       => $validated['amount'],
            'category'     => $validated['category'],
            'date'         => $validated['date'],
            'notes'        => $validated['notes'] ?? null,
            'receipt_path' => $receiptPath,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Expense created.',
            'data'    => $this->format($expense),
        ], 201);
    }

    /**
     * GET /api/expenses/{id}
     */
    public function show(Request $request, int $id)
    {
        $expense = Expense::forTenant($request->user()->id)->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $this->format($expense),
        ]);
    }

    /**
     * PUT /api/expenses/{id}
     */
    public function update(Request $request, int $id)
    {
        $tenantId = $request->user()->id;
        $expense  = Expense::forTenant($tenantId)->findOrFail($id);

        $validated = $request->validate([
            'title'    => 'sometimes|required|string|max:255',
            'amount'   => 'sometimes|required|numeric|min:0',
            'category' => 'sometimes|required|string|max:100',
            'date'     => 'sometimes|required|date',
            'notes'    => 'nullable|string',
            'receipt'  => 'nullable|file|mimes:jpg,jpeg,png,pdf,webp|max:5120',
        ]);

        // Handle new receipt upload
        if ($request->hasFile('receipt') && $request->file('receipt')->isValid()) {
            // Delete old receipt if exists
            if ($expense->receipt_path) {
                Storage::disk('public')->delete($expense->receipt_path);
            }
            $validated['receipt_path'] = $request->file('receipt')->store(
                "receipts/{$tenantId}",
                'public'
            );
        }

        unset($validated['receipt']);
        $expense->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Expense updated.',
            'data'    => $this->format($expense->fresh()),
        ]);
    }

    /**
     * DELETE /api/expenses/{id}
     */
    public function destroy(Request $request, int $id)
    {
        $expense = Expense::forTenant($request->user()->id)->findOrFail($id);

        // Delete receipt file
        if ($expense->receipt_path) {
            Storage::disk('public')->delete($expense->receipt_path);
        }

        $expense->delete();

        return response()->json([
            'success' => true,
            'message' => 'Expense deleted.',
        ]);
    }

    /**
     * GET /api/expenses/summary
     * Totals by category + monthly totals for P&L dashboard.
     */
    public function summary(Request $request)
    {
        $tenantId = $request->user()->id;

        $query = Expense::forTenant($tenantId);

        if ($request->filled('from')) {
            $query->whereDate('date', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('date', '<=', $request->to);
        }

        $expenses = $query->get();

        // Totals by category
        $byCategory = $expenses->groupBy('category')->map(fn ($group) => [
            'category' => $group->first()->category,
            'total'    => round($group->sum('amount'), 2),
            'count'    => $group->count(),
        ])->values();

        // Monthly totals (last 12 months)
        $byMonth = $expenses->groupBy(fn ($e) => $e->date->format('Y-m'))
            ->map(fn ($group, $month) => [
                'month' => $month,
                'total' => round($group->sum('amount'), 2),
            ])
            ->sortKeys()
            ->values();

        return response()->json([
            'success'     => true,
            'data'        => [
                'total'       => round($expenses->sum('amount'), 2),
                'count'       => $expenses->count(),
                'by_category' => $byCategory,
                'by_month'    => $byMonth,
                'categories'  => self::DEFAULT_CATEGORIES,
            ],
        ]);
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    private function format(Expense $e): array
    {
        return [
            'id'           => (string) $e->id,
            'title'        => $e->title,
            'amount'       => (float) $e->amount,
            'category'     => $e->category,
            'date'         => $e->date?->format('Y-m-d'),
            'notes'        => $e->notes,
            'receipt_path' => $e->receipt_path,
            'receipt_url'  => $e->receipt_path
                ? Storage::disk('public')->url($e->receipt_path)
                : null,
            'created_at'   => $e->created_at?->toIso8601String(),
            // Frontend compat aliases
            'createdAt'    => $e->created_at ? $e->created_at->getTimestamp() * 1000 : null,
        ];
    }
}
