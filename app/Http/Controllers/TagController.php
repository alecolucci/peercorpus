<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Article;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TagController extends Controller
{
    // Cria uma nova Tag no Codebook do projeto
    public function store(Request $request, Project $project)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|max:7',
            'parent_id' => 'nullable|exists:tags,id'
        ]);

        $project->tags()->create($validated);

        return back(); // O Inertia atualiza a tela instantaneamente
    }

    // Aplica a Tag a um Artigo (registrando o usuário)
    public function attach(Request $request, Article $article)
    {
        $request->validate(['tag_id' => 'required|exists:tags,id']);

        // Usa o DB facade para inserir na tabela pivot tripla que criamos lá no início
        DB::table('article_tag_user')->updateOrInsert(
            [
                'article_id' => $article->id,
                'tag_id' => $request->tag_id,
                'user_id' => auth()->id(),
            ],
            ['updated_at' => now()]
        );

        return back();
    }

    // Remove a Tag do Artigo
    public function detach(Request $request, Article $article)
    {
        $request->validate(['tag_id' => 'required|exists:tags,id']);

        DB::table('article_tag_user')
            ->where('article_id', $article->id)
            ->where('tag_id', $request->tag_id)
            ->where('user_id', auth()->id())
            ->delete();

        return back();
    }
}
