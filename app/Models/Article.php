<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory;

    // Adicione esta propriedade para permitir a inserção/atualização destes campos
    protected $fillable = [
        'project_id',
        'doi',
        'title',
        'abstract',
        'authors',
        'publication_year',
        'status',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}