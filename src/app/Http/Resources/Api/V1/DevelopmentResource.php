<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DevelopmentResource extends JsonResource
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
            'developer_name' => $this->developer_name,
            'development_type' => $this->development_type,
            'city' => $this->city,
            'province' => $this->province,
            'price_range_min' => $this->price_range_min,
            'price_range_max' => $this->price_range_max,
            'price_range' => $this->priceRange(),
            'total_units' => $this->total_units,
            'available_units' => $this->available_units,
            'logo' => $this->logo,
            'logo_url' => $this->getFirstMediaUrl('logo'),
            'images' => $this->images,
            'is_featured' => $this->is_featured,
            'published_at' => $this->published_at?->toIso8601String(),
        ];
    }
}
