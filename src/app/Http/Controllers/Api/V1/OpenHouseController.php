<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\RsvpRequest;
use App\Models\OpenHouse;
use App\Models\OpenHouseRsvp;
use Illuminate\Http\JsonResponse;

class OpenHouseController extends Controller
{
    /**
     * Register a guest RSVP for an open house event.
     */
    public function rsvp(RsvpRequest $request, OpenHouse $openHouse): JsonResponse
    {
        $data = $request->validated();

        $existing = OpenHouseRsvp::query()
            ->where('open_house_id', $openHouse->id)
            ->where('email', $data['email'])
            ->first();

        if ($existing) {
            if ($existing->status === 'cancelled') {
                $existing->update(['status' => 'confirmed']);

                return response()->json([
                    'message' => 'Your RSVP has been reinstated.',
                    'id' => $existing->id,
                ]);
            }

            return response()->json([
                'message' => 'You have already registered for this event.',
                'id' => $existing->id,
            ], 409);
        }

        if ($openHouse->max_attendees) {
            $confirmed = OpenHouseRsvp::query()
                ->where('open_house_id', $openHouse->id)
                ->where('status', 'confirmed')
                ->count();

            if ($confirmed >= $openHouse->max_attendees) {
                return response()->json(['message' => 'This event is fully booked.'], 422);
            }
        }

        $rsvp = $openHouse->rsvps()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => 'confirmed',
        ]);

        return response()->json([
            'message' => 'You are registered! See you at the open house.',
            'id' => $rsvp->id,
        ], 201);
    }
}
