<!DOCTYPE html>
<html>
<body>
    <h2>Você foi convidado para a equipe do PeerCorpus!</h2>
    <p>Você foi convidado para participar do projeto de revisão sistemática <strong>{{ $project->title }}</strong> com o perfil de <strong>{{ $role }}</strong>.</p>
    <p>Para aceitar e acessar o painel, crie sua conta gratuitamente clicando no link abaixo (certifique-se de usar este mesmo endereço de e-mail no cadastro):</p>
    <a href="{{ route('register') }}" style="display:inline-block; padding:10px 20px; background-color:#4F46E5; color:#fff; text-decoration:none; border-radius:5px;">Criar minha conta</a>
</body>
</html>