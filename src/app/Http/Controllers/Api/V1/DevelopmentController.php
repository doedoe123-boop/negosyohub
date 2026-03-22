<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DevelopmentDetailResource;
use App\Http\Resources\Api\V1\DevelopmentResource;
use App\Models\Development;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DevelopmentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Development::query()
            ->active()
            ->whereNotNull('published_at');

        if ($request->filled('city')) {
            $query->whereRaw('LOWER(city) LIKE ?', ['%'.strtolower($request->city).'%']);
        }

        if ($request->filled('type')) {
            $query->where('development_type', $request->type);
        }

        if ($request->filled('search') && config('scout.driver') !== 'null' && method_exists(Development::class, 'search')) {
            $ids = Development::search((string) $request->search)
                ->take(250)
                ->get()
                ->pluck('id')
                ->all();

            $query->whereIn('id', empty($ids) ? [0] : $ids);
        }

        if ($request->filled('search') && (config('scout.driver') === 'null' || ! method_exists(Development::class, 'search'))) {
            $query->where(function ($q) use ($request) {
                $term = '%'.strtolower($request->search).'%';
                $q->whereRaw('LOWER(name) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(developer_name) LIKE ?', [$term]);
            });
        }

        return DevelopmentResource::collection(
            $query->latest('published_at')->paginate(12)
        );
    }

    public function show(string $slug): DevelopmentDetailResource
    {
        $development = Development::query()
            ->active()
            ->whereNotNull('published_at')
            ->with([
                'properties' => function ($q) {
                    $q->where('status', 'active')
                        ->whereNotNull('published_at')
                        ->with('media')
                        ->latest('published_at')
                        ->limit(24);
                },
            ])
            ->where('slug', $slug)
            ->firstOrFail();

        return new DevelopmentDetailResource($development);
    }
}
