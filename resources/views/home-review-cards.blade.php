@foreach($recentReviews as $review)
    <x-home-review :review="$review" />
@endforeach
