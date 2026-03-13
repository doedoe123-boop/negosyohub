<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\Api\V1\UserInquiryResource;
use App\Models\PropertyInquiry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Update the authenticated user's profile (name, phone).
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->update($request->validated());

        return response()->json($user->fresh());
    }

    /**
     * Change the authenticated user's password.
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $request->user()->update([
            'password' => Hash::make($request->validated('password')),
        ]);

        return response()->json(['message' => 'Password updated successfully.']);
    }

    /**
     * Update per-user notification preferences.
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'notification_preferences' => ['required', 'array'],
            'notification_preferences.order_updates' => ['boolean'],
            'notification_preferences.promotions' => ['boolean'],
        ]);

        $user = $request->user();
        $user->update($validated);

        return response()->json($user->fresh());
    }

    /**
     * Soft-delete the authenticated user's account.
     */
    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();

        // Revoke all tokens so the user is immediately logged out everywhere.
        $user->tokens()->delete();
        $user->delete();

        return response()->json(['message' => 'Account deleted.']);
    }

    /**
     * List the authenticated user's property inquiries.
     */
    public function inquiries(Request $request): AnonymousResourceCollection
    {
        $inquiries = PropertyInquiry::query()
            ->where('user_id', $request->user()->id)
            ->with(['property', 'store'])
            ->latest()
            ->paginate(10);

        return UserInquiryResource::collection($inquiries);
    }

    /**
     * List the authenticated user's unread notifications.
     */
    public function notifications(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->unreadNotifications()
            ->latest()
            ->take(20)
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'type' => class_basename($n->type),
                'data' => $n->data,
                'created_at' => $n->created_at->toISOString(),
            ]);

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markNotificationRead(Request $request, string $id): JsonResponse
    {
        $request->user()
            ->unreadNotifications()
            ->where('id', $id)
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'Notification marked as read.']);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllNotificationsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['message' => 'All notifications marked as read.']);
    }
}
