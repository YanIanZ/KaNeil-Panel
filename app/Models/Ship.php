<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Ship extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'description',
        'author',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $ship) {
            $ship->uuid ??= Str::uuid()->toString();
        });
    }

    public function maps(): HasMany
    {
        return $this->hasMany(Map::class);
    }
}
