<?php

namespace App\Mail;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserAddedToProjectMail extends Mailable
{
    use Queueable, SerializesModels;

    public $project;
    public $role;

    public function __construct(Project $project, $role)
    {
        $this->project = $project;
        $this->role = $role === 'admin' ? 'Administrador' : 'Colaborador';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Você foi adicionado à Revisão: ' . $this->project->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.user_added_to_project',
        );
    }
}
