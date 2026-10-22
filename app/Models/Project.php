<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'status',
        'owner_id',
    ];

    public function articles() {
        return $this->hasMany(Article::class);
    }
    
    public function tags() {
        return $this->hasMany(Tag::class);
    }
    
    public function team() {
        return $this->belongsToMany(User::class, 'project_users')->withPivot('role');
    }

    public function users() {
        return $this->belongsToMany(User::class, 'project_users')->withPivot('role');
    }
}
