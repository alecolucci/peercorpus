<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Article;
use App\Models\Tag;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CodingController extends Controller
{
    // Verifica permissão (Reutilizando a lógica de equipe)
    private function checkAccess(Project $project) {
        $userId = auth()->id();
        $isOwner = $project->owner_id === $userId;
        $isTeamMember = $project->team()->where('user_id', $userId)->exists();
        if (!$isOwner && !$isTeamMember) abort(403);
    }

    // Carrega a tela de Codificação (Paginando 1 artigo por vez)
    public function index(Project $project)
    {
        $this->checkAccess($project);

        // Busca apenas artigos 'included' (aceitos na triagem)
        // Usamos paginate(1) para o Modo Foco. Trazemos também as tags já aplicadas.
        $articles = $project->articles()
            ->where('status', 'included')
            ->with('tags') // Carrega as tags atreladas a este artigo
            ->paginate(1);

        // Carrega o Codebook (Todas as tags do projeto)
        $codebook = $project->tags()->orderBy('name')->get();

        return Inertia::render('Projects/Coding', [
            'project' => $project,
            'articles' => $articles,
            'codebook' => $codebook,
            'stats' => [
                'total_included' => $project->articles()->where('status', 'included')->count(),
                'total_coded' => $project->articles()->whereHas('tags')->count()
            ]
        ]);
    }

    // Cria uma nova Tag no Codebook
    public function storeTag(Request $request, Project $project)
    {
        $this->checkAccess($project);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'color' => 'nullable|string|max:20'
        ]);

        $project->tags()->create([
            'name' => $validated['name'],
            'color' => $validated['color'] ?? '#4F46E5' // Cor padrão se não enviada
        ]);

        return back();
    }

    // Adiciona ou Remove uma Tag de um Artigo (Toggle)
    public function toggleTag(Request $request, Article $article)
    {
        $this->checkAccess($article->project);

        $validated = $request->validate([
            'tag_id' => 'required|exists:tags,id'
        ]);

        // O método toggle() do Laravel faz a mágica: se a tag não está no artigo, ele atrela. Se já está, ele remove.
        $article->tags()->toggle($validated['tag_id']);

        return back();
    }
}
