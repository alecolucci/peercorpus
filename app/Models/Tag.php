<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    public function parent() {
        return $this->belongsTo(Tag::class, 'parent_id');
    }

    public function children() {
        return $this->hasMany(Tag::class, 'parent_id');
    }
}
