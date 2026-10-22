<?php

use App\Models\Project;

Broadcast::channel('project.{id}', function ($user, $id) {
    $project = Project::find($id);
    // Verifica se o utilizador está na equipa deste projeto
    return $project->team->contains($user->id);
});