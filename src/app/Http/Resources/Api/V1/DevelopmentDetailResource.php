<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DevelopmentDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'developer_name' => $this->developer_name,
            'development_type' => $this->development_type,
            'city' => $this->city,
            'province' => $this->province,
            'address_line' => $this->address_line,
            'barangay' => $this->barangay,
            'zip_code' => $this->zip_code,
            'full_location' => $this->fullLocation(),
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'price_range_min' => $this->price_range_min,
            'price_range_max' => $this->price_range_max,
            'price_range' => $this->priceRange(),
            'total_units' => $this->total_units,
            'available_units' => $this->available_units,
            'floors' => $this->floors,
            'year_built' => $this->year_built,
            'amenities' => $this->amenities ?? [],
            'logo' => $this->logo,
            'logo_url' => $this->getFirstMediaUrl('logo'),
            'images' => $this->images ?? [],
            'website_url' => $this->website_url,
            'video_url' => $this->video_url,
            'is_featured' => $this->is_featured,
            'published_at' => $this->published_at?->toIso8601String(),
            'properties' => PropertyResource::collection(
                $this->whenLoaded('properties')
            ),
        ];
    }
}
