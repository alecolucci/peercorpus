<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class ProjectController extends Controller
{
    public function index()
    {
        // O Laravel cria automaticamente a propriedade 'articles_count'
        $projects = auth()->user()->projects()->withCount('articles')->latest()->get();
        
        return Inertia::render('Projects/Index', [
            'projects' => $projects
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        // 1. Cria o projeto definindo o owner_id explicitamente
        $project = \App\Models\Project::create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'owner_id' => auth()->id(),
        ]);
        
        // 2. Regista o utilizador na tabela pivot (project_users) como dono da equipa
        $project->team()->attach(auth()->id(), ['role' => 'owner']);

        return back()->with('success', 'Projeto criado com sucesso!');
    }
}
