<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public announcement endpoints for the customer storefront.
 *
 * Returns only active, non-expired announcements suitable for display
 * as a top-of-page banner or toast notification.
 */
class AnnouncementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Announcement::query()
            ->active()
            ->orderByDesc('published_at')
            ->orderByDesc('created_at');

        if ($request->filled('audience')) {
            $query->where('audience', $request->input('audience'));
        }

        $announcements = $query->limit((int) $request->input('limit', 5))
            ->get(['id', 'title', 'content', 'type', 'audience', 'published_at', 'expires_at']);

        return response()->json($announcements);
    }
}
