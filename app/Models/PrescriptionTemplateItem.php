<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrescriptionTemplateItem extends Model
{
    /** @use HasFactory<\Database\Factories\PrescriptionTemplateItemFactory> */
    use HasFactory;

    protected $guarded = [];

    public function prescriptionTemplate(): BelongsTo
    {
        return $this->belongsTo(PrescriptionTemplate::class);
    }
}
