<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use App\Models\ProjectInvitation;
use App\Mail\InviteColleagueMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ProjectMemberController extends Controller
{
    public function store(Request $request, Project $project)
    {
        $validated = $request->validate([
            'email' => 'required|email' // Removemos o "exists:users,email"
        ]);

        $email = $validated['email'];
        $user = User::where('email', $email)->first();

        // CÁLCULO 1: O utilizador já tem conta na plataforma?
        if ($user) {
            if ($project->users()->where('user_id', $user->id)->exists()) {
                return back()->with('error', 'Este pesquisador já está no projeto.');
            }

            $project->users()->attach($user->id, ['role' => 'collaborator']);
            return back()->with('success', 'Pesquisador adicionado com sucesso!');
        }

        // CÁLCULO 2: O utilizador não tem conta. Vamos verificar se já o convidamos.
        if (ProjectInvitation::where('project_id', $project->id)->where('email', $email)->exists()) {
            return back()->with('error', 'Um convite já foi enviado para este e-mail.');
        }

        // Gera o convite e o token
        $token = Str::random(32);
        ProjectInvitation::create([
            'project_id' => $project->id,
            'email' => $email,
            'token' => $token
        ]);

        // Envia o E-mail
        Mail::to($email)->send(new InviteColleagueMail($project, $token, $email));

        return back()->with('success', 'Convite enviado com sucesso para o e-mail informado!');
    }
}
