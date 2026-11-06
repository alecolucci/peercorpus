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
        $userId = $request->user()->id;

        $projects = Project::where('owner_id', $userId)
            ->orWhereHas('team', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            // Carrega a relação com a equipe especificamente para o usuário logado
            ->with(['team' => function($query) use ($userId) {
                $query->where('user_id', $userId);
            }])
            ->withCount('articles')
            ->latest()
            ->get()
            // Injeta a permissão de edição diretamente no objeto do projeto
            ->map(function ($project) use ($userId) {
                $isOwner = $project->owner_id === $userId;
                $teamRole = $project->team->first()?->pivot->role;
                
                $project->can_edit = $isOwner || $teamRole === 'admin';
                return $project;
            });

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
        // Verifica se é dono OU se é membro da equipe
        $isOwner = $project->owner_id === auth()->id();
        $isTeamMember = $project->team()->where('user_id', auth()->id())->exists();

        if (!$isOwner && !$isTeamMember) {
            abort(403, 'Você não tem acesso a este projeto.');
        }

        $project->loadCount([
            'articles', 
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
        $isOwner = $project->owner_id === auth()->id();
        $isAdmin = $project->team()->where('user_id', auth()->id())->where('role', 'admin')->exists();

        // Apenas Donos ou Administradores podem editar título/descrição
        if (!$isOwner && !$isAdmin) abort(403, 'Você não tem permissão para editar os dados deste projeto.');

        return Inertia::render('Projects/Edit', [
            'project' => $project
        ]);
    }

    public function update(Request $request, Project $project)
    {
        $isOwner = $project->owner_id === auth()->id();
        $isAdmin = $project->team()->where('user_id', auth()->id())->where('role', 'admin')->exists();

        if (!$isOwner && !$isAdmin) abort(403);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $project->update($validated);

        return redirect()->route('projects.index')->with('success', 'Projeto atualizado!');
    }

    public function destroy(Project $project)
    {
        // APENAS o dono absoluto pode deletar o projeto e apagar a pesquisa
        if ($project->owner_id !== auth()->id()) abort(403, 'Apenas o proprietário pode excluir o projeto.');

        $project->delete();

        return redirect()->route('projects.index')->with('success', 'Projeto excluído.');
    }
}