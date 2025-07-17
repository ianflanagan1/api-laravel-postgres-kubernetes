<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Item
 */
class ItemPublicFullResource extends JsonResource
{
    public const array COLUMNS = ['uuid', 'name', 'type'];

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,    // Rename `uuid` to `id`
            'name' => $this->name,
            'type' => $this->type,
        ];

        // Keep aligned with Tests\Feature\ItemControllerTest::itemFullStructure()
    }
}
