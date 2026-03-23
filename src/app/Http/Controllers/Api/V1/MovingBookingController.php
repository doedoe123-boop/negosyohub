<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CreateMovingBookingRequest;
use App\Http\Requests\Api\V1\UpdateMovingBookingStatusRequest;
use App\Models\MovingBooking;
use App\MovingBookingStatus;
use App\Services\MovingBookingService;
use Illuminate\Http\JsonResponse;

class MovingBookingController extends Controller
{
    public function __construct(private MovingBookingService $service) {}

    public function index(): JsonResponse
    {
        $bookings = MovingBooking::query()
            ->where('customer_user_id', auth()->id())
            ->with(['store', 'addOns'])
            ->latest()
            ->paginate(15);

        return response()->json($bookings);
    }

    public function show(MovingBooking $movingBooking): JsonResponse
    {
        $this->authorize('view', $movingBooking);

        $movingBooking->load(['store', 'addOns', 'rentalAgreement', 'review']);

        return response()->json($movingBooking);
    }

    public function store(CreateMovingBookingRequest $request): JsonResponse
    {
        $this->authorize('create', MovingBooking::class);

        $booking = $this->service->createBooking($request->user(), $request->validated());

        return response()->json($booking, 201);
    }

    public function updateStatus(UpdateMovingBookingStatusRequest $request, MovingBooking $movingBooking): JsonResponse
    {
        $this->authorize('updateStatus', $movingBooking);

        $updated = $this->service->updateStatus($movingBooking, $request->enum('status', MovingBookingStatus::class));

        return response()->json($updated);
    }

    public function cancel(MovingBooking $movingBooking): JsonResponse
    {
        $this->authorize('cancel', $movingBooking);

        $cancelled = $this->service->updateStatus($movingBooking, MovingBookingStatus::Cancelled);

        return response()->json($cancelled);
    }
}
