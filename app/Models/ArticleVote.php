<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticleVote extends Model
{
    protected $fillable = ['article_id', 'user_id', 'vote', 'is_resolution'];

    public function user() {
        return $this->belongsTo(User::class);
    }
    public function article() {
        return $this->belongsTo(Article::class);
    }
}
