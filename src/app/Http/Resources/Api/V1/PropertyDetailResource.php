<?php

namespace App\Http\Resources\Api\V1;

use App\Models\MovingBooking;
use App\Models\Property;
use App\Models\PropertyInquiry;
use App\Models\RentalAgreement;
use App\PropertyStatus;
use App\Support\HtmlSanitizer;
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
        $user = $request->user('sanctum');
        $userInquiry = null;
        $rentalAgreement = null;
        $movingBooking = null;

        if ($user !== null) {
            $userInquiry = PropertyInquiry::query()
                ->where('property_id', $this->id)
                ->where('user_id', $user->id)
                ->latest('id')
                ->first();

            $rentalAgreement = RentalAgreement::query()
                ->where('property_id', $this->id)
                ->where('tenant_user_id', $user->id)
                ->whereIn('status', ['pending', 'negotiating', 'signed', 'active'])
                ->latest('id')
                ->first();

            if ($rentalAgreement !== null) {
                $movingBooking = MovingBooking::query()
                    ->where('rental_agreement_id', $rentalAgreement->id)
                    ->where('customer_user_id', $user->id)
                    ->latest('id')
                    ->first();
            }
        }

        $landlordCreatedAt = $this->store?->owner?->created_at;
        $landlordAccountAgeDays = $landlordCreatedAt?->diffInDays(now());

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => HtmlSanitizer::sanitize($this->description),
            'property_type' => $this->property_type->value,
            'property_type_label' => $this->property_type->label(),
            'listing_type' => $this->listing_type->value,
            'listing_type_label' => $this->listing_type->label(),
            'status' => $this->status->value,
            'is_active' => $this->status === PropertyStatus::Active,
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
            'is_verified_landlord' => $this->verifiedLandlordSignal(),
            'is_suspicious_listing' => $this->suspiciousListingSignal(),
            'trust_signals' => [
                'landlord_account_age_days' => $landlordAccountAgeDays,
                'landlord_account_age_label' => $landlordAccountAgeDays === null
                    ? null
                    : ($landlordAccountAgeDays >= 365
                        ? floor($landlordAccountAgeDays / 365).' year'.(floor($landlordAccountAgeDays / 365) === 1 ? '' : 's')
                        : $landlordAccountAgeDays.' day'.($landlordAccountAgeDays === 1 ? '' : 's')),
                'warning_banner' => $this->store?->isPaupahan()
                    ? 'Do not send money outside the platform. Schedule viewings and confirm rental terms here before moving in.'
                    : null,
            ],

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
                'created_at' => $this->store->created_at?->toIso8601String(),
            ]),

            // Inquiry awareness (authenticated users only)
            'has_inquired' => $this->when(
                $user !== null,
                fn () => $userInquiry !== null,
            ),

            // Rental agreement awareness (authenticated users only)
            'has_rented' => $this->when(
                $user !== null,
                fn () => $rentalAgreement !== null,
            ),

            'rental_journey' => $this->when(
                $user !== null && $this->store?->isPaupahan(),
                fn () => [
                    'inquiry_submitted' => $userInquiry !== null,
                    'inquiry_status' => $userInquiry?->status?->value,
                    'inquiry_status_label' => $userInquiry?->status?->label(),
                    'viewing_scheduled_at' => $userInquiry?->viewing_date?->toIso8601String(),
                    'agreement_id' => $rentalAgreement?->id,
                    'agreement_status' => $rentalAgreement?->status,
                    'agreement_signed_at' => $rentalAgreement?->signed_at?->toIso8601String(),
                    'move_in_date' => $rentalAgreement?->move_in_date?->toDateString(),
                    'moving_booking_id' => $movingBooking?->id,
                    'moving_booking_status' => $movingBooking?->status?->value,
                    'ready_for_move_in' => $rentalAgreement !== null,
                ],
            ),

            // SEO
            'seo_title' => $this->seo_title,
            'seo_description' => $this->seo_description,
            'seo_keywords' => $this->seo_keywords,
        ];
    }
}
