<?php

declare(strict_types=1);

namespace App\Models;

use App\Http\Resources\ItemPublicFullResource;
use App\Http\Resources\ItemPublicMinimalResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Item extends Model
{
    /** @use HasFactory<\Database\Factories\ItemFactory> */
    use HasFactory;

    public $incrementing = false;

    protected $fillable = [
        'name',
        'type',
    ];

    protected $keyType = 'string';

    // Create uuid for new rows
    public static function booted()
    {
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }

    /**
     * Scope a query to only include columns for public full display (i.e. `show` method).
     *
     * @return Builder<static>
     */
    public static function queryForPublicFull(): Builder
    {
        return static::query()->select(ItemPublicFullResource::COLUMNS);
    }

    /**
     * Scope a query to only include columns for public minimal display (i.e. `index` method).
     *
     * @return Builder<static>
     */
    public static function queryForPublicMinimal(): Builder
    {
        return static::query()->select(ItemPublicMinimalResource::COLUMNS);
    }

    // Use the 'uuid' column for route model binding
    public function getRouteKeyName()
    {
        return 'uuid';
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
