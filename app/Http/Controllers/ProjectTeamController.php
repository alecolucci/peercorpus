<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use App\Models\ProjectInvitation;
use App\Mail\ProjectInvitationMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ProjectTeamController extends Controller
{
    // Pega a permissão do usuário logado neste projeto
    private function getCurrentUserRole(Project $project)
    {
        $member = $project->team()->where('user_id', auth()->id())->first();
        return $member ? $member->pivot->role : null;
    }

    // Tela de listagem
    public function index(Project $project)
    {
        $role = $this->getCurrentUserRole($project);
        if (!$role) abort(403, 'Você não faz parte deste projeto.');

        return Inertia::render('Projects/Team', [
            'project' => $project,
            'team' => $project->team()->get(['users.id', 'users.name', 'users.email']),
            'invitations' => $project->invitations, // <-- ADICIONADO AQUI
            'currentUserRole' => $role
        ]);
    }

    // Adicionar membro
    public function store(Request $request, Project $project)
    {
        $role = $this->getCurrentUserRole($project);
        if (!in_array($role, ['owner', 'admin'])) abort(403);

        // REMOVIDO a regra "exists:users,email" para permitir e-mails não cadastrados
        $validated = $request->validate([
            'email' => 'required|email',
            'role' => ['required', \Illuminate\Validation\Rule::in(['admin', 'collaborator'])]
        ]);

        $email = strtolower($validated['email']);
        $userToAdd = User::where('email', $email)->first();

        // CENÁRIO 1: O usuário JÁ EXISTE no sistema
        if ($userToAdd) {
            if ($project->team()->where('user_id', $userToAdd->id)->exists()) {
                return back()->withErrors(['email' => 'Este usuário já faz parte do projeto.']);
            }
            $project->team()->attach($userToAdd->id, ['role' => $validated['role']]);
            return back()->with('success', 'Membro adicionado com sucesso!');
        }

        // CENÁRIO 2: Usuário NÃO EXISTE (Convite)
        if ($project->invitations()->where('email', $email)->exists()) {
            return back()->withErrors(['email' => 'Um convite já foi enviado para este e-mail.']);
        }

        $project->invitations()->create([
            'email' => $email,
            'role' => $validated['role']
        ]);

        // Dispara o E-mail
        Mail::to($email)->send(new ProjectInvitationMail($project, $validated['role']));

        return back()->with('success', 'Convite enviado por e-mail!');
    }

    // Cancelar um convite pendente
    public function destroyInvitation(Project $project, ProjectInvitation $invitation)
    {
        $role = $this->getCurrentUserRole($project);
        if (!in_array($role, ['owner', 'admin'])) abort(403);

        $invitation->delete();
        return back()->with('success', 'Convite cancelado.');
    }

    // Atualizar papel (role)
    public function update(Request $request, Project $project, User $user)
    {
        $currentUserRole = $this->getCurrentUserRole($project);
        if (!in_array($currentUserRole, ['owner', 'admin'])) abort(403);

        $validated = $request->validate([
            'role' => ['required', Rule::in(['admin', 'collaborator'])]
        ]);

        $targetUser = $project->team()->where('user_id', $user->id)->first();
        if (!$targetUser) abort(404);

        // Regra de Ouro: Ninguém pode alterar o proprietário (owner)
        if ($targetUser->pivot->role === 'owner') {
            return back()->withErrors(['role' => 'Não é possível alterar as permissões do proprietário.']);
        }

        $project->team()->updateExistingPivot($user->id, ['role' => $validated['role']]);

        return back()->with('success', 'Permissões atualizadas!');
    }

    // Remover membro
    public function destroy(Project $project, User $user)
    {
        $currentUserRole = $this->getCurrentUserRole($project);
        if (!in_array($currentUserRole, ['owner', 'admin'])) abort(403);

        $targetUser = $project->team()->where('user_id', $user->id)->first();
        if (!$targetUser) abort(404);

        // Regra de Ouro: Ninguém exclui o proprietário
        if ($targetUser->pivot->role === 'owner') {
            abort(403, 'O proprietário não pode ser removido do projeto.');
        }

        $project->team()->detach($user->id);

        return back()->with('success', 'Membro removido da equipe.');
    }
}
