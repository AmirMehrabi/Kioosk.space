<?php

namespace App\Support;

use App\Models\Business;
use App\Models\Review;
use App\Models\User;
use App\Services\BusinessHours;
use Illuminate\Support\Collection;

class SchemaOrg
{
    /**
     * @param  Collection<int, Review>  $reviews
     * @param  Collection<int, mixed>  $photos
     */
    public static function business(Business $business, Collection $reviews, Collection $photos): array
    {
        $url = route('businesses.show', $business->slug);
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => self::businessType($business->category?->name),
            '@id' => $url.'#business',
            'name' => $business->name,
            'url' => $url,
            'description' => $business->description,
            'category' => $business->category?->name,
            'image' => $photos->map(fn ($photo): string => route('media.show', $photo))->values()->all(),
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $business->address,
                'addressLocality' => $business->location?->name ?? $business->city,
                'addressCountry' => 'IR',
            ],
            'telephone' => $business->phones[0]['value'] ?? $business->phone,
            'sameAs' => collect($business->websites)->pluck('url')->filter()->values()->all(),
            'openingHoursSpecification' => self::openingHours($business->weekly_hours),
            'review' => $reviews->map(fn (Review $review): array => self::review($review, $url))->all(),
        ];

        if ($business->latitude !== null && $business->longitude !== null) {
            $schema['geo'] = ['@type' => 'GeoCoordinates', 'latitude' => $business->latitude, 'longitude' => $business->longitude];
        }

        if ($business->reviews_count > 0) {
            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => round((float) $business->reviews_avg_rating, 1),
                'bestRating' => 5,
                'worstRating' => 1,
                'ratingCount' => $business->reviews_count,
                'reviewCount' => $business->reviews_count,
            ];
        }

        return self::withoutEmptyValues($schema);
    }

    /** @param array<int, array{name: string, url: string}> $items */
    public static function breadcrumbs(array $items): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($items)->values()->map(fn (array $item, int $index): array => [
                '@type' => 'ListItem', 'position' => $index + 1, 'name' => $item['name'], 'item' => $item['url'],
            ])->all(),
        ];
    }

    public static function person(User $user): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            '@id' => route('users.show', $user->slug).'#person',
            'name' => $user->name,
            'url' => route('users.show', $user->slug),
        ];
    }

    private static function review(Review $review, string $businessUrl): array
    {
        return [
            '@type' => 'Review',
            '@id' => route('reviews.show', $review).'#review',
            'itemReviewed' => ['@id' => $businessUrl.'#business'],
            'author' => ['@type' => 'Person', 'name' => $review->author->name, 'url' => route('users.show', $review->author->slug)],
            'datePublished' => $review->created_at->toDateString(),
            'reviewRating' => ['@type' => 'Rating', 'ratingValue' => $review->rating, 'bestRating' => 5, 'worstRating' => 1],
            'reviewBody' => $review->body,
        ];
    }

    private static function businessType(?string $category): string
    {
        $normalized = BusinessIdentity::normalize($category ?? '');

        return match (true) {
            str_contains($normalized, 'رستوران'), str_contains($normalized, 'restaurant') => 'Restaurant',
            str_contains($normalized, 'کافه'), str_contains($normalized, 'cafe'), str_contains($normalized, 'coffee') => 'CafeOrCoffeeShop',
            str_contains($normalized, 'خرید'), str_contains($normalized, 'فروشگاه'), str_contains($normalized, 'store'), str_contains($normalized, 'shop') => 'Store',
            default => 'LocalBusiness',
        };
    }

    /** @return array<int, array<string, mixed>> */
    private static function openingHours(?array $schedule): array
    {
        $dayNames = [
            'saturday' => 'https://schema.org/Saturday',
            'sunday' => 'https://schema.org/Sunday',
            'monday' => 'https://schema.org/Monday',
            'tuesday' => 'https://schema.org/Tuesday',
            'wednesday' => 'https://schema.org/Wednesday',
            'thursday' => 'https://schema.org/Thursday',
            'friday' => 'https://schema.org/Friday',
        ];

        return collect(BusinessHours::DAYS)->flatMap(function (string $day) use ($schedule, $dayNames): array {
            return collect($schedule[$day]['shifts'] ?? [])->map(fn (array $shift): array => [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => $dayNames[$day],
                'opens' => $shift['opens'],
                'closes' => $shift['closes'],
            ])->all();
        })->values()->all();
    }

    private static function withoutEmptyValues(array $data): array
    {
        return array_filter($data, fn (mixed $value): bool => $value !== null && $value !== '' && $value !== []);
    }
}
