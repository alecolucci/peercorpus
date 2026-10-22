<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Article;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ScreeningController extends Controller
{
    public function index(Project $project)
    {
        // Conta o progresso
        $totalArticles = $project->articles()->count();
        $screenedCount = $project->articles()->whereNotNull('status')->count();

        // Traz apenas 1 artigo que ainda não tem status definido
        $currentArticle = $project->articles()
            ->whereNull('status')
            ->first();

        return Inertia::render('Screening/Index', [
            'project' => $project,
            'article' => $currentArticle,
            'progress' => [
                'total' => $totalArticles,
                'screened' => $screenedCount,
                'percentage' => $totalArticles > 0 ? round(($screenedCount / $totalArticles) * 100) : 0,
            ]
        ]);
    }

    public function update(Request $request, Project $project, Article $article)
    {
        $validated = $request->validate(['status' => 'required|in:included,excluded']);
        
        // Proteção contra concorrência: verifica se alguém já triou enquanto a tela estava aberta
        if ($article->status !== null) {
            return back()->with('error', 'Este artigo já foi avaliado por outro pesquisador.');
        }

        $article->update(['status' => $validated['status']]);

        // Avisa os outros pesquisadores!
        \App\Events\ArticleScreened::dispatch($project->id, $article->id, auth()->user()->name);

        return back();
    }
}