<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicalRecordPhoto extends Model
{
    /** @use HasFactory<\Database\Factories\MedicalRecordPhotoFactory> */
    use HasFactory;

    protected $guarded = [];

    public function medicalRecord(): BelongsTo
    {
        return $this->belongsTo(MedicalRecord::class);
    }
}
