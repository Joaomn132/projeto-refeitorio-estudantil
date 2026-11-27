<?php
// Cabeçalho comum. Antes de incluir, a página define $titulo (e, se quiser, $msg).
$titulo = $titulo ?? 'Refeitório Estudantil';
$msgExibir = $msg ?? null;
if (!$msgExibir && isset($_SESSION['flash'])) {
    $msgExibir = $_SESSION['flash'];
}
unset($_SESSION['flash']);
$tabelaAtual = $t ?? '';
?>
<!DOCTYPE html>
<html lang="pt-br" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($titulo) ?> - Refeitório Estudantil</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2.0.6/css/pico.min.css">
<style>
    /* ============================================================
       Design System — Refeitório Estudantil (sobre o Pico.css)
       ============================================================ */
    :root {
        /* Marca — azul institucional sóbrio */
        --marca:        #2a5d8f;
        --marca-forte:  #214c76;
        --acento:       #2a5d8f;

        /* Superfícies e texto (tema claro) */
        --bg:           #f3f4f6;
        --superficie:   #ffffff;
        --superficie-2: #f7f8fa;
        --borda:        #e4e6eb;
        --borda-forte:  #d2d6dd;
        --texto:        #1c2430;
        --texto-suave:  #5b6673;

        /* Estados */
        --ok-bg: #edf6ee; --ok-bd: #c3e2c7; --ok-tx: #2c6b38;
        --erro-bg: #fbeeed; --erro-bd: #eccbc8; --erro-tx: #9e342d;
        --avi-bg: #fbf4e6; --avi-bd: #ecd9ad; --avi-tx: #8a5a16;

        /* Tokens do Pico */
        --pico-primary: var(--marca);
        --pico-primary-hover: var(--marca-forte);
        --pico-primary-background: var(--marca);
        --pico-primary-hover-background: var(--marca-forte);
        --pico-primary-focus: rgba(42, 93, 143, .22);
        --pico-primary-inverse: #fff;
        --pico-border-radius: 6px;
        --pico-spacing: 1rem;
        --pico-font-family: "Inter", system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
        --raio: 8px;
        --raio-sm: 6px;
    }

    /* ----- Tema escuro automático ----- */
    @media (prefers-color-scheme: dark) {
        :root:not([data-theme="light"]) {
            --bg: #10151c; --superficie: #171d26; --superficie-2: #1b222c;
            --borda: #28313d; --borda-forte: #3a4553;
            --texto: #dfe4ea; --texto-suave: #93a0ad;
            --marca: #5b9bd1; --marca-forte: #74adde;
            --ok-bg: #122a1b; --ok-bd: #2a5236; --ok-tx: #8fd3a0;
            --erro-bg: #2b1513; --erro-bd: #5e2b27; --erro-tx: #e8a6a0;
            --avi-bg: #2a2310; --avi-bd: #5a4a1f; --avi-tx: #e5c47c;
        }
    }
    :root[data-theme="dark"] {
        --bg: #10151c; --superficie: #171d26; --superficie-2: #1b222c;
        --borda: #28313d; --borda-forte: #3a4553;
        --texto: #dfe4ea; --texto-suave: #93a0ad;
        --marca: #5b9bd1; --marca-forte: #74adde;
        --ok-bg: #122a1b; --ok-bd: #2a5236; --ok-tx: #8fd3a0;
        --erro-bg: #2b1513; --erro-bd: #5e2b27; --erro-tx: #e8a6a0;
        --avi-bg: #2a2310; --avi-bd: #5a4a1f; --avi-tx: #e5c47c;
    }

    body {
        background: var(--bg);
        color: var(--texto);
        font-family: var(--pico-font-family);
        line-height: 1.55;
    }

    /* ===== Barra superior ===== */
    header.topo {
        background: var(--superficie);
        border-bottom: 1px solid var(--borda);
        padding: 0 clamp(16px, 4vw, 32px);
    }
    header.topo .topo-inner {
        max-width: 1120px; margin: 0 auto;
        display: flex; align-items: center; flex-wrap: wrap; gap: 0 28px;
    }
    header.topo .marca {
        display: flex; align-items: center; gap: 11px;
        font-size: 1.02rem; font-weight: 600; letter-spacing: -.01em; color: var(--texto);
        margin: 0; margin-right: auto; padding: 16px 0; text-decoration: none;
    }
    header.topo .marca .logo {
        width: 34px; height: 34px; border-radius: 7px; flex: 0 0 auto;
        display: grid; place-items: center; color: #fff; background: var(--marca);
    }
    header.topo .marca .logo svg { width: 19px; height: 19px; }
    header.topo .marca small {
        display: block; font-size: .72rem; font-weight: 400;
        letter-spacing: 0; text-transform: none; color: var(--texto-suave);
    }
    header.topo nav {
        display: flex; flex-wrap: wrap; align-self: stretch; padding: 0;
    }
    header.topo nav a {
        display: flex; align-items: center;
        color: var(--texto-suave); text-decoration: none;
        padding: 0 14px; font-size: .9rem; font-weight: 500;
        border-bottom: 2px solid transparent; margin-bottom: -1px;
        transition: color .15s, border-color .15s;
    }
    header.topo nav a:hover { color: var(--texto); }
    header.topo nav a.ativo {
        color: var(--marca); border-bottom-color: var(--marca); font-weight: 600;
    }

    /* Área de acesso no canto direito */
    header.topo .painel-acesso {
        display: flex; align-items: center; gap: 10px; padding-left: 14px; margin-left: 4px;
    }
    .cadeado {
        display: grid; place-items: center; width: 38px; height: 38px;
        border-radius: 8px; color: var(--texto-suave);
        border: 1px solid var(--borda); background: var(--superficie);
        transition: color .15s, border-color .15s, background .15s;
    }
    .cadeado:hover { color: var(--marca); border-color: var(--borda-forte); background: var(--superficie-2); }
    .cadeado svg { width: 18px; height: 18px; }
    .chip-admin {
        font-size: .72rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase;
        color: var(--marca); background: color-mix(in srgb, var(--marca) 12%, transparent);
        border: 1px solid color-mix(in srgb, var(--marca) 28%, transparent);
        padding: 3px 9px; border-radius: 999px;
    }
    .sair {
        display: inline-flex; align-items: center; gap: 7px;
        color: var(--texto-suave); text-decoration: none; font-size: .88rem; font-weight: 500;
        padding: 7px 12px; border-radius: 8px; border: 1px solid var(--borda); background: var(--superficie);
        transition: color .15s, border-color .15s, background .15s;
    }
    .sair:hover { color: var(--erro-tx); border-color: var(--erro-bd); background: var(--erro-bg); }
    .sair svg { width: 16px; height: 16px; }

    /* ===== Conteúdo ===== */
    main { max-width: 1120px; margin: 28px auto 56px; padding: 0 clamp(16px, 4vw, 24px); }
    h2 { margin-top: 0; font-weight: 600; letter-spacing: -.01em; font-size: 1.3rem; }

    /* ===== Cartões ===== */
    .card {
        background: var(--superficie);
        border: 1px solid var(--borda); border-radius: var(--raio);
        padding: 22px 24px; margin-bottom: 20px;
    }
    .card > *:last-child { margin-bottom: 0; }
    .card > h2:first-child {
        padding-bottom: 14px; margin-bottom: 18px;
        border-bottom: 1px solid var(--borda);
    }

    /* ===== Grade de atalhos (tela inicial) ===== */
    .grade { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 14px; }
    .grade a {
        display: block; background: var(--superficie);
        border: 1px solid var(--borda); border-radius: var(--raio);
        padding: 20px; text-decoration: none; color: var(--texto);
        transition: border-color .15s, background .15s;
    }
    .grade a:hover { border-color: var(--borda-forte); background: var(--superficie-2); }
    .grade .icone {
        width: 38px; height: 38px; border-radius: 8px; margin-bottom: 16px;
        display: grid; place-items: center; color: var(--marca);
        background: var(--superficie-2); border: 1px solid var(--borda);
    }
    .grade .icone svg { width: 20px; height: 20px; }
    .grade strong {
        display: block; font-size: 1.9rem; line-height: 1; font-weight: 700;
        letter-spacing: -.02em; color: var(--texto); margin-bottom: 5px;
    }
    .grade .rotulo { color: var(--texto-suave); font-weight: 500; font-size: .9rem; }

    /* ===== Tabelas ===== */
    .tabela-wrap {
        overflow-x: auto; border: 1px solid var(--borda);
        border-radius: var(--raio-sm); background: var(--superficie);
    }
    .card table { margin: 0; }
    .card th {
        background: var(--superficie-2); color: var(--texto-suave);
        font-size: .78rem; font-weight: 600; letter-spacing: .01em;
        border-bottom: 1px solid var(--borda); white-space: nowrap;
    }
    .card td { border-bottom: 1px solid var(--borda); vertical-align: middle; }
    .card tbody tr:last-child td { border-bottom: 0; }
    .card tbody tr:hover td { background: var(--superficie-2); }

    /* ===== Botões ===== */
    .btn {
        display: inline-flex; align-items: center; justify-content: center; gap: 6px;
        width: auto; background: var(--marca); color: #fff; border: 1px solid var(--marca);
        border-radius: var(--raio-sm); padding: 8px 16px; font-size: .88rem; font-weight: 500;
        line-height: 1.2; cursor: pointer; text-decoration: none; margin: 0;
        transition: background .15s, border-color .15s;
    }
    .btn:hover { background: var(--marca-forte); border-color: var(--marca-forte); }
    .btn.sec {
        background: var(--superficie); color: var(--texto); border-color: var(--borda-forte);
    }
    .btn.sec:hover { background: var(--superficie-2); border-color: var(--texto-suave); }
    .btn.perigo { background: #b5362e; border-color: #b5362e; }
    .btn.perigo:hover { background: #972c25; border-color: #972c25; }
    .btn.peq { padding: 5px 11px; font-size: .8rem; }

    /* ===== Mensagens ===== */
    .msg {
        padding: 12px 15px; border-radius: var(--raio-sm); margin-bottom: 20px;
        font-size: .92rem; border: 1px solid;
    }
    .msg.ok { background: var(--ok-bg); border-color: var(--ok-bd); color: var(--ok-tx); }
    .msg.erro { background: var(--erro-bg); border-color: var(--erro-bd); color: var(--erro-tx); }

    /* ===== Badges de status ===== */
    .badge {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 3px 9px; border-radius: 4px; font-size: .74rem; font-weight: 600;
        letter-spacing: .01em; border: 1px solid transparent; white-space: nowrap;
    }
    .badge::before {
        content: ""; width: 6px; height: 6px; border-radius: 50%; background: currentColor;
    }
    .badge.ok  { background: var(--ok-bg); color: var(--ok-tx); border-color: var(--ok-bd); }
    .badge.mau { background: var(--erro-bg); color: var(--erro-tx); border-color: var(--erro-bd); }
    .badge.esp { background: var(--avi-bg); color: var(--avi-tx); border-color: var(--avi-bd); }
    .badge.neu { background: var(--superficie-2); color: var(--texto-suave); border-color: var(--borda); }

    /* ===== Formulários ===== */
    label { font-weight: 500; font-size: .88rem; margin-bottom: 4px; color: var(--texto); }
    label small { color: var(--texto-suave); font-weight: 400; }
    input, select, textarea {
        max-width: 440px; border-radius: var(--raio-sm) !important;
        background: var(--superficie) !important; color: var(--texto) !important;
    }
    input:focus, select:focus, textarea:focus {
        box-shadow: 0 0 0 3px var(--pico-primary-focus) !important;
    }

    /* ===== Bloco de SQL ===== */
    pre {
        background: var(--superficie-2); color: var(--texto);
        padding: 14px 16px; border: 1px solid var(--borda); border-radius: var(--raio-sm);
        overflow-x: auto; font-size: .82rem; line-height: 1.5;
        font-family: ui-monospace, "SF Mono", Menlo, Consolas, monospace;
    }

    form.inline { display: inline; margin: 0; }
    .acoes { white-space: nowrap; display: flex; gap: 6px; align-items: center; }
    .vazio { color: var(--texto-suave); }
    h3 { font-weight: 600; letter-spacing: -.01em; }

    /* ===== Blocos de métrica / estatística ===== */
    .metricas { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 14px; }
    .stat { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-top: 4px; }
    .bloco {
        background: var(--superficie-2); border: 1px solid var(--borda);
        border-radius: var(--raio); padding: 16px 18px;
    }
    .bloco .rot {
        font-size: .76rem; font-weight: 600; letter-spacing: .02em;
        text-transform: uppercase; color: var(--texto-suave);
    }
    .bloco .val {
        font-size: 1.55rem; font-weight: 700; letter-spacing: -.02em;
        margin-top: 6px; color: var(--texto);
    }
    .bloco .val.pos { color: var(--ok-tx); }
    .bloco .val.neg { color: var(--erro-tx); }
    .bloco .val.avi { color: var(--avi-tx); }

    /* ===== Cardápio (área do estudante) ===== */
    .cardapio { display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: 14px; }
    .prato {
        border: 1px solid var(--borda); border-radius: var(--raio);
        background: var(--superficie-2); padding: 16px 18px;
        display: flex; flex-direction: column; gap: 8px;
    }
    .prato-topo { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
    .prato-data { font-size: .8rem; color: var(--texto-suave); }
    .prato-nome { font-weight: 600; line-height: 1.35; }
    .prato-preco { font-size: .95rem; font-weight: 700; color: var(--marca); margin-top: auto; }
    .tag {
        font-size: .72rem; font-weight: 700; letter-spacing: .02em; text-transform: uppercase;
        padding: 2px 9px; border-radius: 999px; border: 1px solid transparent;
    }
    .tag-almoco { background: var(--avi-bg); color: var(--avi-tx); border-color: var(--avi-bd); }
    .tag-jantar { background: color-mix(in srgb, var(--marca) 12%, transparent); color: var(--marca); border-color: color-mix(in srgb, var(--marca) 28%, transparent); }

    /* ===== Seletor de aluno ===== */
    .seletor { margin-bottom: 20px; }
    .seletor-linha { display: flex; gap: 10px; align-items: center; }
    .seletor select { max-width: 360px; width: 100%; }

    /* ===== Tela de login ===== */
    .login-wrap { max-width: 420px; margin: 24px auto; }
    .login-card { text-align: center; padding: 32px 30px; }
    .login-selo {
        width: 54px; height: 54px; margin: 0 auto 16px; border-radius: 14px;
        display: grid; place-items: center; color: var(--marca);
        background: color-mix(in srgb, var(--marca) 12%, transparent);
        border: 1px solid color-mix(in srgb, var(--marca) 24%, transparent);
    }
    .login-selo svg { width: 26px; height: 26px; }
    .login-card h2 { margin-bottom: 4px; }
    .login-sub { color: var(--texto-suave); margin-bottom: 22px; font-size: .92rem; }
    .login-form { text-align: left; }
    .login-form label { display: block; margin-top: 12px; }
    .login-form input { max-width: none; width: 100%; }
    .login-voltar { margin: 18px 0 0; font-size: .88rem; }
    .login-voltar a { color: var(--texto-suave); }

    @media (max-width: 640px) {
        header.topo .marca small { display: none; }
        header.topo .chip-admin, header.topo .sair span { display: none; }
        .card { padding: 18px; }
    }
</style>
</head>
<body>
<?php $paginaAtual = basename($_SERVER['PHP_SELF']); $ehAdmin = admin_logado(); ?>
<header class="topo">
    <div class="topo-inner">
        <a class="marca" href="index.php">
            <span class="logo" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M3 2v7c0 1.1.9 2 2 2h0a2 2 0 0 0 2-2V2"/><path d="M5 11v11"/><path d="M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7"/></svg>
            </span>
            <span>Refeitório Estudantil<small><?= $ehAdmin ? 'Área administrativa' : 'Área do estudante' ?></small></span>
        </a>

        <nav>
        <?php if ($ehAdmin): ?>
            <a href="admin.php" class="<?= $paginaAtual === 'admin.php' ? 'ativo' : '' ?>">Métricas</a>
            <?php foreach ($TABELAS as $nome => $cfgNav): ?>
                <a href="crud.php?t=<?= h($nome) ?>" class="<?= $tabelaAtual === $nome ? 'ativo' : '' ?>"><?= h($cfgNav['titulo']) ?></a>
            <?php endforeach; ?>
            <a href="relatorios.php" class="<?= $paginaAtual === 'relatorios.php' ? 'ativo' : '' ?>">Relatórios</a>
        <?php else: ?>
            <a href="index.php" class="<?= $paginaAtual === 'index.php' ? 'ativo' : '' ?>">Início</a>
        <?php endif; ?>
        </nav>

        <div class="painel-acesso">
        <?php if ($ehAdmin): ?>
            <span class="chip-admin">Admin</span>
            <a class="sair" href="logout.php" title="Sair da área administrativa">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
                <span>Sair</span>
            </a>
        <?php else: ?>
            <a class="cadeado" href="login.php" title="Acesso administrativo" aria-label="Acesso administrativo">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            </a>
        <?php endif; ?>
        </div>
    </div>
</header>
<main>
<?php if ($msgExibir): ?>
    <div class="msg <?= h($msgExibir[0]) ?>"><?= h($msgExibir[1]) ?></div>
<?php endif; ?>
