<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Article;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ScreeningController extends Controller
{
    private function getUserRole(Project $project, $userId) {
        if ($project->owner_id === $userId) return 'owner';
        $member = $project->team()->where('user_id', $userId)->first();
        return $member ? $member->pivot->role : null;
    }

    public function index(Request $request, Project $project)
    {
        $userId = auth()->id();
        $role = $this->getUserRole($project, $userId);

        if (!$role) abort(403, 'Você não tem permissão para acessar a triagem deste projeto.');

        $currentFilter = $request->query('filter', 'pending');

        // Carrega os artigos e INJETA os votos e os nomes de quem votou
        $articles = $project->articles()
            ->with(['votes.user:id,name']) 
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
            'conflict' => $project->articles()->where('status', 'conflict')->count(), // Nova estatística
        ];

        return Inertia::render('Projects/Screening', [
            'project' => $project,
            'articles' => $articles,
            'stats' => $stats,
            'currentFilter' => $currentFilter,
            'currentUserRole' => $role // Enviamos o papel para o Vue saber se mostra o botão de desempate
        ]);
    }

    public function updateStatus(Request $request, Article $article)
    {
        $userId = auth()->id();
        $role = $this->getUserRole($article->project, $userId);
        if (!$role) abort(403);

        $validated = $request->validate([
            'status' => 'required|in:pending,included,excluded',
            'is_resolution' => 'boolean'
        ]);

        $isResolution = $request->input('is_resolution', false);

        if ($isResolution && !in_array($role, ['owner', 'admin'])) {
            abort(403, 'Apenas administradores podem resolver conflitos.');
        }

        // 1. Grava ou Remove o voto individual
        if ($validated['status'] === 'pending') {
            $article->votes()->where('user_id', $userId)->where('is_resolution', $isResolution)->delete();
        } else {
            $article->votes()->updateOrCreate(
                ['user_id' => $userId, 'is_resolution' => $isResolution],
                ['vote' => $validated['status']]
            );
        }

        // 2. Recalcula o Status Geral do Artigo
        $resolutionVote = $article->votes()->where('is_resolution', true)->latest()->first();
        
        if ($resolutionVote) {
            // Se o Admin deu o veredito, a palavra dele é lei
            $article->update(['status' => $resolutionVote->vote]);
        } else {
            // Calcula democraticamente
            $votes = $article->votes()->where('is_resolution', false)->pluck('vote');
            
            if ($votes->contains('included') && $votes->contains('excluded')) {
                $article->update(['status' => 'conflict']);
            } elseif ($votes->isNotEmpty()) {
                $article->update(['status' => $votes->first()]); // Todos votaram igual
            } else {
                $article->update(['status' => 'pending']); // Nenhum voto restante
            }
        }

        return back();
    }
}
