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
        // Garante que o usuário tem acesso ao projeto
        if ($project->owner_id !== auth()->id()) abort(403);

        // Captura o filtro atual (padrão: pendentes)
        $currentFilter = $request->query('filter', 'pending');

        $articles = $project->articles()
            ->when($currentFilter !== 'all', function ($query) use ($currentFilter) {
                $query->where('status', $currentFilter);
            })
            ->paginate(5) // Paginação curta de 5 em 5 para não cansar o usuário
            ->withQueryString(); // Mantém o filtro na URL ao mudar de página

        // Estatísticas para a barra de progresso
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
        // Validação de segurança
        $validated = $request->validate([
            'status' => 'required|in:pending,included,excluded,conflict'
        ]);

        $article->update(['status' => $validated['status']]);

        // Retorna silenciosamente. O Inertia atualiza os dados na tela automaticamente.
        return back();
    }
}
