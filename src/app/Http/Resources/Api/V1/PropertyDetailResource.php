<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

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
            'is_active' => $this->status === \App\PropertyStatus::Active,
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
            // Rich data
            'features' => $this->features,
            'nearby_places' => $this->nearby_places,
            'direction_steps' => collect($this->direction_steps)->map(fn ($step) => [
                'instruction' => $step['instruction'] ?? null,
                'landmark' => $step['landmark'] ?? null,
                'transport_mode' => $step['transport_mode'] ?? null,
                'photo' => ! empty($step['photo']) ? Storage::disk('public')->url($step['photo']) : null,
            ]),
            'floor_plans' => collect($this->floor_plans)->map(fn ($p) => [
                'label' => $p['label'] ?? null,
                'floor_number' => $p['floor_number'] ?? null,
                'url' => ! empty($p['url']) ? Storage::disk('public')->url($p['url']) : null,
            ]),
            'documents' => collect($this->documents)->map(fn ($d) => [
                'name' => $d['name'] ?? null,
                'type' => $d['type'] ?? null,
                'url' => ! empty($d['url']) ? Storage::disk('public')->url($d['url']) : null,
            ]),
            'house_rules' => $this->house_rules,
            'utility_inclusions' => $this->utility_inclusions,
            'safety_features' => $this->safety_features,

            // Flags & stats
            'is_featured' => $this->is_featured,
            'views_count' => $this->views_count,
            'average_rating' => $this->average_rating,
            'review_count' => $this->review_count,
            'reviews' => $this->testimonials()
                ->where('is_published', true)
                ->latest()
                ->limit(10)
                ->get()
                ->map(fn ($t) => [
                    'id' => $t->id,
                    'name' => $t->client_name,
                    'rating' => $t->rating,
                    'title' => $t->title,
                    'content' => $t->content,
                    'verified' => false,
                    'date' => $t->created_at?->diffForHumans() ?? 'Recently',
                ])
                ->toArray(),
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
                'sector_template' => $this->store->sector_template,
            ]),

            // Inquiry awareness (authenticated users only)
            'has_inquired' => $this->when(
                $request->user('sanctum') !== null,
                fn () => \App\Models\PropertyInquiry::query()
                    ->where('property_id', $this->id)
                    ->where('user_id', $request->user('sanctum')->id)
                    ->exists(),
            ),

            // Rental agreement awareness (authenticated users only)
            'has_rented' => $this->when(
                $request->user('sanctum') !== null,
                fn () => \App\Models\RentalAgreement::query()
                    ->where('property_id', $this->id)
                    ->where('tenant_user_id', $request->user('sanctum')->id)
                    ->whereIn('status', ['pending', 'negotiating', 'signed', 'active'])
                    ->exists(),
            ),
        ];
    }
}
