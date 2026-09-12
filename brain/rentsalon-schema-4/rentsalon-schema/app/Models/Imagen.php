<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Imagen extends Model
{
    protected $fillable = ['url', 'orden'];

    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }
}
