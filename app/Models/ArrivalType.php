<?php

namespace App\Models;

use App\Application\Arrival\Enums\ArrivalKind;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'slug',
])]
class ArrivalType extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'slug' => ArrivalKind::class,
        ];
    }

    public function arrivals(): HasMany
    {
        return $this->hasMany(Arrival::class);
    }
}
