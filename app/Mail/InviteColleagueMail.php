<?php

namespace App\Mail;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InviteColleagueMail extends Mailable
{
    use Queueable, SerializesModels;

    public $project;
    public $registrationUrl;

    public function __construct(Project $project, $token, $email)
    {
        $this->project = $project;
        // Gera a URL para a página de registo do Breeze, já passando o email na URL para facilitar
        $this->registrationUrl = route('register', ['email' => $email, 'token' => $token]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Convite para colaborar no projeto: ' . $this->project->title,
        );
    }

    public function content(): Content
    {
        // Vamos usar uma view HTML simples em vez de Markdown para ser mais rápido
        return new Content(
            view: 'emails.invite',
        );
    }
}
