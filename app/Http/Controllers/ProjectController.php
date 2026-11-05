<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProjectController extends Controller
{
    // Listar projetos
    public function index(Request $request)
    {
        $projects = Project::where('owner_id', $request->user()->id)
            ->withCount('articles') // Traz a contagem de artigos já importados
            ->latest()
            ->get();

        return Inertia::render('Projects/Index', [
            'projects' => $projects
        ]);
    }

    // Tela de criação
    public function create()
    {
        return Inertia::render('Projects/Create');
    }

    // Salvar novo projeto
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        // 1. Cria o projeto inserindo o owner_id diretamente na tabela projects
        $project = Project::create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'status' => 'setup',
            'owner_id' => $request->user()->id,
        ]);

        // 2. Insere o criador na tabela pivot (project_users) como dono
        $project->team()->attach($request->user()->id, ['role' => 'owner']);

        return redirect()->route('projects.index')->with('success', 'Projeto criado com sucesso!');
    }

    // Painel do Projeto (Onde ficará o importador)
    public function show(Project $project)
    {
        // Garante que o usuário tem permissão
        if ($project->owner_id !== auth()->id()) {
            abort(403);
        }

        // Carrega as estatísticas do projeto de uma só vez com subqueries otimizadas
        $project->loadCount([
            'articles', // Total geral
            'articles as pending_count' => function ($query) { $query->where('status', 'pending'); },
            'articles as included_count' => function ($query) { $query->where('status', 'included'); },
            'articles as excluded_count' => function ($query) { $query->where('status', 'excluded'); },
        ]);

        return Inertia::render('Projects/Show', [
            'project' => $project
        ]);
    }

    // Tela de edição
    public function edit(Project $project)
    {
        if ($project->owner_id !== auth()->id()) abort(403);

        return Inertia::render('Projects/Edit', [
            'project' => $project
        ]);
    }

    // Atualizar projeto
    public function update(Request $request, Project $project)
    {
        if ($project->owner_id !== auth()->id()) abort(403);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $project->update($validated);

        return redirect()->route('projects.index')->with('success', 'Projeto atualizado!');
    }

    // Remover projeto
    public function destroy(Project $project)
    {
        if ($project->owner_id !== auth()->id()) abort(403);

        $project->delete();

        return redirect()->route('projects.index')->with('success', 'Projeto excluído.');
    }
}