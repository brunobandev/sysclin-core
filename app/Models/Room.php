<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Room extends Model
{
    protected $guarded = [];

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }
}
