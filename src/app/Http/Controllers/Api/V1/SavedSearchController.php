<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreSavedSearchRequest;
use App\Http\Resources\Api\V1\SavedSearchResource;
use App\Models\SavedSearch;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class SavedSearchController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $searches = $request->user()
            ->savedSearches()
            ->latest()
            ->get();

        return SavedSearchResource::collection($searches);
    }

    public function store(StoreSavedSearchRequest $request): SavedSearchResource
    {
        $savedSearch = $request->user()->savedSearches()->create($request->validated());

        return new SavedSearchResource($savedSearch);
    }

    public function destroy(Request $request, SavedSearch $savedSearch): Response
    {
        abort_if($savedSearch->user_id !== $request->user()->id, 403);

        $savedSearch->delete();

        return response()->noContent();
    }

    public function toggle(Request $request, SavedSearch $savedSearch): SavedSearchResource
    {
        abort_if($savedSearch->user_id !== $request->user()->id, 403);

        $savedSearch->update(['is_active' => ! $savedSearch->is_active]);

        return new SavedSearchResource($savedSearch->fresh());
    }
}
