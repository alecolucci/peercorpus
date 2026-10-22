<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class SynthesisController extends Controller
{
    public function index(Project $project)
    {
        // 1. Traz apenas os artigos aprovados na triagem
        $articles = $project->articles()
            ->where('status', 'included')
            ->select('id', 'title', 'authors', 'publication_year', 'doi')
            ->get();

        // 2. Traz o codebook (tags do projeto)
        $tags = $project->tags()->select('id', 'name', 'color')->get();

        // 3. Busca todas as ligações de tags nos artigos do projeto
        $pivotData = DB::table('article_tag_user')
            ->whereIn('article_id', $articles->pluck('id'))
            ->get();

        return Inertia::render('Synthesis/Index', [
            'project' => $project,
            'articles' => $articles,
            'tags' => $tags,
            'pivotData' => $pivotData,
        ]);
    }
}
