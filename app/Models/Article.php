<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'title',
        'abstract',
        'authors',
        'publication_year',
        'doi',
        'status',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function votes()
    {
        return $this->hasMany(ArticleVote::class);
    }
}
