<x-mail::message>
# New properties matching "{{ $savedSearch->name }}"

Hi there! We found {{ $properties->count() }} new {{ Illuminate\Support\Str::plural('listing', $properties->count()) }} that match your saved search.

<x-mail::table>
| Property | Location | Price |
| -------- | -------- | ----- |
@foreach ($properties as $property)
| **{{ $property->title }}** | {{ $property->city }}, {{ $property->province }} | {{ $property->formatted_price }} |
@endforeach
</x-mail::table>

<x-mail::button :url="config('app.url').'/properties'">
View All Listings
</x-mail::button>

You are receiving this because you saved a property search on {{ config('app.name') }}.
To stop these emails, manage your saved searches in your account.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
