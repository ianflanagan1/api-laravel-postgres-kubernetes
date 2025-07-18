<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ItemIndexRequest;
use App\Http\Requests\ItemStoreUpdateRequest;
use App\Http\Resources\ItemPublicFullResource;
use App\Http\Resources\ItemPublicMinimalResource;
use App\Models\Item;
use App\Models\User;
use App\Services\ApiResponseBuilder;
use App\Traits\HandlesDbErrors;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ItemController extends Controller
{
    use HandlesDbErrors;

    /**
     * Display a listing of the resource.
     */
    public function index(ItemIndexRequest $request): JsonResponse
    {
        return ApiResponseBuilder::paginated(
            Item::queryForPublicMinimal()->orderBy('id', 'asc')->paginate(
                $request->getPerPage(),
                ['*'],
                'page',
                $request->getPage(),
            ),
            ItemPublicMinimalResource::class,
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ItemStoreUpdateRequest $request): JsonResponse
    {
        return $this->handleDbErrors(function () use ($request): JsonResponse {
            /**
             * @var User $user
             *           Ensured by ApiAuthMiddleware::class in authenticated endpoints
             */
            $user = $request->user();
            $item = $user->items()->create($request->validated());

            return ApiResponseBuilder::success(
                new ItemPublicFullResource($item),
                SymfonyResponse::HTTP_CREATED
            );

            // return DB::transaction(function () use ($item): JsonResponse {
            //     return ApiResponseBuilder::success(
            //         new ItemPublicFullResource($item),
            //         SymfonyResponse::HTTP_CREATED
            //     );
            // }, 1);
        });
    }

    /**
     * Display the specified resource.
     */
    public function show(string $uuid): JsonResponse
    {
        $item = Item::queryForPublicFull()->firstWhere('uuid', $uuid);

        if ($item === null) {
            return ApiResponseBuilder::not_found();
        }

        return ApiResponseBuilder::success(
            new ItemPublicFullResource($item),
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ItemStoreUpdateRequest $request, string $uuid): JsonResponse
    {
        return $this->handleDbErrors(function () use ($request, $uuid): JsonResponse {
            $validated = $request->validated();

            $affectedRows = Item::where('uuid', $uuid)->update($validated);

            if ($affectedRows === 0) {
                return ApiResponseBuilder::not_found();
            }

            $item = new Item($validated);
            $item->uuid = $uuid;
            $item->exists = true;

            return ApiResponseBuilder::success(new ItemPublicFullResource($item));
        });
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $uuid): JsonResponse
    {
        $affectedRows = Item::where('uuid', $uuid)->delete();

        if ($affectedRows === 0) {
            return ApiResponseBuilder::not_found();
        }

        return ApiResponseBuilder::success();
    }
}
