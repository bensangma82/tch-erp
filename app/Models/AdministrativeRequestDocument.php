<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdministrativeRequestDocument extends Model
{
    protected $fillable = [
        'administrative_request_id',
        'document_type',
        'title',
        'file_name',
        'file_path',
        'storage_disk',
        'mime_type',
        'file_size',
        'uploaded_by',
        'uploaded_at',
        'remarks',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
        'file_size' => 'integer',
    ];


    public function administrativeRequest(): BelongsTo
    {
        return $this->belongsTo(
            AdministrativeRequest::class
        );
    }


    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'uploaded_by'
        );
    }

    public function documents(): HasMany
{
    return $this->hasMany(
        AdministrativeRequestDocument::class
    )->latest('uploaded_at');
}

}