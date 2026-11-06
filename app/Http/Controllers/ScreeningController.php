<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Article;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ScreeningController extends Controller
{
    public function index(Request $request, Project $project)
    {
        $userId = auth()->id();
        $isOwner = $project->owner_id === $userId;
        $isTeamMember = $project->team()->where('user_id', $userId)->exists();

        // Libera se for dono ou membro da equipe
        if (!$isOwner && !$isTeamMember) {
            abort(403, 'Você não tem permissão para acessar a triagem deste projeto.');
        }

        $currentFilter = $request->query('filter', 'pending');

        $articles = $project->articles()
            ->when($currentFilter !== 'all', function ($query) use ($currentFilter) {
                $query->where('status', $currentFilter);
            })
            ->paginate(5)
            ->withQueryString();

        $stats = [
            'total' => $project->articles()->count(),
            'pending' => $project->articles()->where('status', 'pending')->count(),
            'included' => $project->articles()->where('status', 'included')->count(),
            'excluded' => $project->articles()->where('status', 'excluded')->count(),
        ];

        return Inertia::render('Projects/Screening', [
            'project' => $project,
            'articles' => $articles,
            'stats' => $stats,
            'currentFilter' => $currentFilter
        ]);
    }

    public function updateStatus(Request $request, Article $article)
    {
        // Proteção de rota: garante que quem está votando faz parte do projeto daquele artigo
        $project = $article->project;
        $userId = auth()->id();
        
        $isOwner = $project->owner_id === $userId;
        $isTeamMember = $project->team()->where('user_id', $userId)->exists();

        if (!$isOwner && !$isTeamMember) abort(403);

        $validated = $request->validate([
            'status' => 'required|in:pending,included,excluded,conflict'
        ]);

        $article->update(['status' => $validated['status']]);

        return back();
    }
}
