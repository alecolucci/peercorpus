<?php

namespace App\Mail;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProjectInvitationMail extends Mailable
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
            subject: 'Convite para participar da Revisão: ' . $this->project->title,
        );
    }

    public function content(): Content
    {
        // O Laravel permite passar views HTML limpas, mas vamos criar uma view na pasta emails
        return new Content(
            view: 'emails.project_invitation',
        );
    }
}