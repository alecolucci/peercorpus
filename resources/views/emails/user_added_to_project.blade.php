<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; color: #333; line-height: 1.5;">
    <h2>Bem-vindo à equipe da revisão!</h2>
    <p>Você acaba de ser adicionado ao projeto de revisão sistemática <strong>{{ $project->title }}</strong> com o perfil de <strong>{{ $role }}</strong>.</p>
    <p>O projeto já está vinculado à sua conta e disponível no seu painel. Acesse a plataforma agora mesmo para começar a colaborar:</p>
    <br>
    <a href="{{ route('dashboard') }}" style="display:inline-block; padding:10px 20px; background-color:#4F46E5; color:#fff; text-decoration:none; border-radius:5px; font-weight: bold;">
        Acessar meu Dashboard
    </a>
</body>
</html>