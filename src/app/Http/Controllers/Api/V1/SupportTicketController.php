<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\TicketCategory;
use App\TicketPriority;
use App\TicketStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupportTicketController extends Controller
{
    /**
     * Display a listing of the user's support tickets.
     */
    public function index(Request $request): JsonResponse
    {
        $tickets = SupportTicket::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json($tickets);
    }

    /**
     * Store a newly created support ticket.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'min:10'],
            'category' => ['required', Rule::enum(TicketCategory::class)],
            'priority' => ['required', Rule::enum(TicketPriority::class)],
            'sector' => ['nullable', 'string', 'max:50'],
            'store_id' => ['nullable', 'exists:stores,id'],
        ]);

        $ticket = SupportTicket::create([
            ...$validated,
            'user_id' => $request->user()->id,
            'status' => TicketStatus::Open,
        ]);

        return response()->json([
            'message' => 'Support ticket submitted successfully.',
            'data' => $ticket,
        ], 201);
    }

    /**
     * Display the specified support ticket.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $ticket = SupportTicket::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json($ticket);
    }
}
