<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Jobs\ProcessArticleImport;
use Illuminate\Http\Request;

class ArticleImportController extends Controller
{
    public function import(Request $request, Project $project)
    {
        // Agora podemos permitir ficheiros maiores (ex: até 50MB)
        $request->validate([
            'bibliography_file' => 'required|file|max:51200', 
        ]);

        // Guarda o arquivo fisicamente na pasta storage/app/imports
        $path = $request->file('bibliography_file')->store('imports');

        // Despachar o Job para a fila
        ProcessArticleImport::dispatch($path, $project->id)->delay(now()->addSeconds(3));

        // O utilizador recebe a resposta instantaneamente, sem ficar à espera do processamento
        return back()->with('success', 'Upload iniciado! O arquivo está sendo processado em background.');
    }
}
