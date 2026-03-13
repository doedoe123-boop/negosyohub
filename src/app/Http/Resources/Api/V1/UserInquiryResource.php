<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Represents a property inquiry as seen by the customer who submitted it.
 *
 * @mixin \App\Models\PropertyInquiry
 */
class UserInquiryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'message' => $this->message,
            'viewing_date' => $this->viewing_date?->toDateString(),
            'agent_notes' => $this->agent_notes,
            'created_at' => $this->created_at?->toIso8601String(),

            'property' => [
                'id' => $this->property?->id,
                'title' => $this->property?->title,
                'slug' => $this->property?->slug,
                'price' => $this->property?->price,
                'formatted_price' => $this->property?->formatted_price,
                'listing_type' => $this->property?->listing_type?->value,
                'city' => $this->property?->city,
                'featured_image' => $this->property?->featured_image,
            ],

            'store' => [
                'name' => $this->store?->name,
                'slug' => $this->store?->slug,
                'sector_template' => $this->store?->template()?->value,
            ],
        ];
    }
}
