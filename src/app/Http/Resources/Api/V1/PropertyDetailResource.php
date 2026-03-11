<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Full property representation for the detail / show view.
 *
 * @mixin Property
 */
class PropertyDetailResource extends JsonResource
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
            'description' => $this->description,
            'property_type' => $this->property_type->value,
            'property_type_label' => $this->property_type->label(),
            'listing_type' => $this->listing_type->value,
            'listing_type_label' => $this->listing_type->label(),
            'status' => $this->status->value,
            'price' => $this->price,
            'price_currency' => $this->price_currency,
            'price_period' => $this->price_period,
            'formatted_price' => $this->formattedPrice(),

            // Specs
            'bedrooms' => $this->bedrooms,
            'bathrooms' => $this->bathrooms,
            'garage_spaces' => $this->garage_spaces,
            'floor_area' => $this->floor_area,
            'lot_area' => $this->lot_area,
            'year_built' => $this->year_built,
            'floors' => $this->floors,

            // Unit info (condos / developments)
            'unit_number' => $this->unit_number,
            'unit_floor' => $this->unit_floor,

            // Location
            'address_line' => $this->address_line,
            'barangay' => $this->barangay,
            'city' => $this->city,
            'province' => $this->province,
            'zip_code' => $this->zip_code,
            'full_address' => $this->fullLocation(),
            'latitude' => $this->latitude ? (float) $this->latitude : null,
            'longitude' => $this->longitude ? (float) $this->longitude : null,

            // Media & links
            'images' => $this->images,
            'floor_plans' => $this->floor_plans,
            'documents' => $this->documents,
            'video_url' => $this->video_url,
            'virtual_tour_url' => $this->virtual_tour_url,

            // Rich data
            'features' => $this->features,
            'nearby_places' => $this->nearby_places,

            // Flags & stats
            'is_featured' => $this->is_featured,
            'views_count' => $this->views_count,
            'average_rating' => $this->average_rating,
            'review_count' => $this->review_count,
            'published_at' => $this->published_at?->toIso8601String(),

            // Development
            'development' => $this->whenLoaded('development', fn () => [
                'id' => $this->development->id,
                'name' => $this->development->name,
                'slug' => $this->development->slug,
                'developer_name' => $this->development->developer_name,
            ]),

            // Store / Agent
            'store' => $this->whenLoaded('store', fn () => [
                'id' => $this->store->id,
                'name' => $this->store->name,
                'slug' => $this->store->slug,
                'logo_url' => $this->store->logo_url,
                'agent_name' => $this->store->agent_name,
                'agent_photo_url' => $this->store->agent_photo_url,
                'agent_bio' => $this->store->agent_bio,
                'phone' => $this->store->phone,
            ]),
        ];
    }
}
