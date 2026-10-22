<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    // Relacionamento hierárquico (Pai/Filho)
    public function parent() {
        return $this->belongsTo(Tag::class, 'parent_id');
    }

    public function children() {
        return $this->hasMany(Tag::class, 'parent_id');
    }

    protected $fillable = ['project_id', 'parent_id', 'name', 'color'];
}
