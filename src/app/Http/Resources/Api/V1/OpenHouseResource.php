<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OpenHouseResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'event_date' => $this->event_date?->toDateString(),
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'max_attendees' => $this->max_attendees,
            'rsvp_count' => $this->whenCounted('rsvps'),
            'is_virtual' => $this->is_virtual,
            'virtual_link' => $this->when($this->is_virtual, $this->virtual_link),
            'status' => $this->status,
        ];
    }
}
