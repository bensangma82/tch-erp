<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sonologist extends Model
{
    protected $fillable = [
        'name',
        'qualification',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function diagnosticResults(): HasMany
    {
        return $this->hasMany(
            DiagnosticResult::class,
            'reporting_sonologist_id'
        );
    }
}
