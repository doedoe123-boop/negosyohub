<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CreateMovingReviewRequest;
use App\Models\MovingBooking;
use App\Models\MovingReview;
use App\MovingBookingStatus;
use Illuminate\Http\JsonResponse;

class MovingReviewController extends Controller
{
    public function store(CreateMovingReviewRequest $request, MovingBooking $movingBooking): JsonResponse
    {
        abort_unless($movingBooking->customer_user_id === auth()->id(), 403);
        abort_unless($movingBooking->status === MovingBookingStatus::Completed, 422, 'You can only review completed bookings.');
        abort_if($movingBooking->review()->exists(), 422, 'A review for this booking already exists.');

        $review = MovingReview::create([
            'moving_booking_id' => $movingBooking->id,
            'store_id' => $movingBooking->store_id,
            'customer_user_id' => auth()->id(),
            'rating' => $request->integer('rating'),
            'comment' => $request->input('comment'),
            'is_published' => true,
        ]);

        return response()->json($review, 201);
    }
}
