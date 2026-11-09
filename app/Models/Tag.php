<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    protected $fillable = ['project_id', 'parent_id', 'name', 'color'];

    public function project() {
        return $this->belongsTo(Project::class);
    }

    public function articles() {
        return $this->belongsToMany(Article::class);
    }
    
    public function parent() {
        return $this->belongsTo(Tag::class, 'parent_id');
    }

    public function children() {
        return $this->hasMany(Tag::class, 'parent_id');
    }
}
