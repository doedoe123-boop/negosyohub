<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Slim property representation for listing / card views.
 *
 * @mixin Property
 */
class PropertyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'property_type' => $this->property_type->value,
            'property_type_label' => $this->property_type->label(),
            'listing_type' => $this->listing_type->value,
            'listing_type_label' => $this->listing_type->label(),
            'status' => $this->status->value,
            'price' => $this->price,
            'price_currency' => $this->price_currency,
            'price_period' => $this->price_period,
            'formatted_price' => $this->formattedPrice(),
            'bedrooms' => $this->bedrooms,
            'bathrooms' => $this->bathrooms,
            'garage_spaces' => $this->garage_spaces,
            'floor_area' => $this->floor_area,
            'lot_area' => $this->lot_area,
            'city' => $this->city,
            'province' => $this->province,
            'images' => $this->images,
            'is_featured' => $this->is_featured,
            'views_count' => $this->views_count,
            'average_rating' => $this->average_rating,
            'review_count' => $this->review_count,
            'published_at' => $this->published_at?->toIso8601String(),
            'store' => $this->whenLoaded('store', fn () => [
                'id' => $this->store->id,
                'name' => $this->store->name,
                'slug' => $this->store->slug,
                'logo_url' => $this->store->logo_url,
            ]),
        ];
    }
}
