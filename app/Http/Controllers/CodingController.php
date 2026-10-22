<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class CodingController extends Controller
{
    public function index(Project $project)
    {
        // 1. Traz todas as tags do projeto (O Codebook)
        $tags = $project->tags()->get();

        // 2. Traz os artigos INCLUÍDOS na triagem
        $articles = $project->articles()
            ->where('status', 'included')
            ->get();

        // 3. Busca todas as marcações (quais tags estão em quais artigos, feitas por este usuário)
        $appliedTags = DB::table('article_tag_user')
            ->whereIn('article_id', $articles->pluck('id'))
            ->where('user_id', auth()->id())
            ->get();

        return Inertia::render('Coding/Index', [
            'project' => $project,
            'articles' => $articles,
            'tags' => $tags,
            'appliedTags' => $appliedTags
        ]);
    }
}
