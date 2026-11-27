<?php
// Tela de acesso do administrador.
require 'config.php';

if (admin_logado()) {
    header('Location: admin.php');
    exit;
}

$erro = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['usuario'] ?? '');
    $s = (string)($_POST['senha'] ?? '');
    if ($u === ADMIN_USUARIO && $s === ADMIN_SENHA) {
        session_regenerate_id(true);
        $_SESSION['admin'] = true;
        header('Location: admin.php');
        exit;
    }
    $erro = 'Usuário ou senha incorretos.';
}

$titulo = 'Acesso administrativo';
require 'header.php';
?>
<div class="login-wrap">
    <div class="card login-card">
        <div class="login-selo" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        </div>
        <h2>Acesso administrativo</h2>
        <p class="login-sub">Entre com suas credenciais para gerenciar o refeitório.</p>

        <?php if ($erro): ?><div class="msg erro"><?= h($erro) ?></div><?php endif; ?>

        <form method="post" class="login-form">
            <label for="usuario">Usuário</label>
            <input id="usuario" name="usuario" type="text" autocomplete="username" autofocus required>

            <label for="senha">Senha</label>
            <input id="senha" name="senha" type="password" autocomplete="current-password" required>

            <button class="btn" type="submit" style="width:100%;margin-top:14px">Entrar</button>
        </form>
        <p class="login-voltar"><a href="index.php">← Voltar à área do estudante</a></p>
    </div>
</div>
<?php require 'footer.php'; ?>
