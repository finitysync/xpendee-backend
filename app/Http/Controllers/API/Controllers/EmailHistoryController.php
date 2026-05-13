<?php

namespace App\Http\Controllers\API\Controllers;

use App\Http\Controllers\Controller;
use App\Models\EmailHistory;
use Illuminate\Http\Request;

class EmailHistoryController extends Controller
{
    /**
     * GET /api/email-history
     * List all sent emails for the tenant.
     */
    public function index(Request $request)
    {
        $history = EmailHistory::forTenant($request->user()->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($h) => $this->format($h));

        return response()->json([
            'success' => true,
            'data'    => $history,
        ]);
    }

    /**
     * DELETE /api/email-history/{id}
     */
    public function destroy(Request $request, int $id)
    {
        $history = EmailHistory::forTenant($request->user()->id)->findOrFail($id);
        $history->delete();

        return response()->json([
            'success' => true,
            'message' => 'History entry deleted.',
        ]);
    }

    // ─── Format ───────────────────────────────────────────────────────────────

    private function format(EmailHistory $h): array
    {
        return [
            'id'            => (string) $h->id,
            'to'            => $h->to_email,
            'subject'       => $h->subject,
            'type'          => $h->type,
            'relatedId'     => $h->related_id ? (string) $h->related_id : null,
            'status'        => $h->status,
            'error'         => $h->error_message,
            'createdAt'     => $h->created_at ? $h->created_at->getTimestamp() * 1000 : null,
            'formattedDate' => $h->created_at?->format('d M Y, H:i'),
        ];
    }
}
