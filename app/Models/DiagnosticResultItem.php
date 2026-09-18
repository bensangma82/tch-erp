<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiagnosticResultItem extends Model
{
    protected $fillable = [
        'diagnostic_result_id',
        'parameter_name',
        'result_value',
        'unit',
        'reference_range',
        'flag',
        'sort_order',
        'remarks',
    ];


    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }


    public function diagnosticResult(): BelongsTo
    {
        return $this->belongsTo(
            DiagnosticResult::class
        );
    }
}