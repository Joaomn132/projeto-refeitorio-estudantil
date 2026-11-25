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
<html lang="pt-br">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($titulo) ?> - Refeitório Estudantil</title>
<style>
    * { box-sizing: border-box; }
    body { font-family: Arial, Helvetica, sans-serif; margin: 0; background: #f4f6f8; color: #222; }
    header { background: #1f4e79; color: #fff; padding: 14px 20px; }
    header h1 { margin: 0 0 10px; font-size: 20px; }
    nav a { color: #dce9f5; text-decoration: none; margin-right: 14px; padding: 4px 0; display: inline-block; }
    nav a:hover, nav a.ativo { color: #fff; border-bottom: 2px solid #fff; }
    main { max-width: 1100px; margin: 20px auto; padding: 0 16px; }
    h2 { margin-top: 0; }
    table { border-collapse: collapse; width: 100%; background: #fff; }
    th, td { border: 1px solid #d5dbe1; padding: 8px 10px; text-align: left; font-size: 14px; }
    th { background: #e8eef4; }
    .tabela-wrap { overflow-x: auto; }
    .card { background: #fff; border: 1px solid #d5dbe1; border-radius: 6px; padding: 16px; margin-bottom: 20px; }
    .grade { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 14px; }
    .grade a { background: #fff; border: 1px solid #d5dbe1; border-radius: 6px; padding: 16px;
               text-decoration: none; color: #1f4e79; display: block; }
    .grade a:hover { border-color: #1f4e79; }
    .grade strong { display: block; font-size: 26px; }
    .btn { display: inline-block; background: #1f4e79; color: #fff; border: 0; border-radius: 4px;
           padding: 8px 14px; font-size: 14px; cursor: pointer; text-decoration: none; }
    .btn.sec { background: #6b7a89; }
    .btn.perigo { background: #b3261e; }
    .btn.peq { padding: 4px 9px; font-size: 13px; }
    .msg { padding: 12px 14px; border-radius: 4px; margin-bottom: 16px; }
    .msg.ok { background: #e3f4e6; border: 1px solid #8fcf9a; }
    .msg.erro { background: #fde8e6; border: 1px solid #e59a94; }
    label { display: block; margin-top: 12px; font-weight: bold; font-size: 14px; }
    input, select { width: 100%; max-width: 420px; padding: 8px; margin-top: 4px; font-size: 14px; }
    small { color: #555; font-weight: normal; }
    pre { background: #1e2a36; color: #e6edf3; padding: 12px; border-radius: 4px; overflow-x: auto; font-size: 13px; }
    form.inline { display: inline; }
    .acoes { white-space: nowrap; }
</style>
</head>
<body>
<header>
    <h1>Sistema do Refeitório Estudantil</h1>
    <nav>
        <a href="index.php">Início</a>
        <?php foreach ($TABELAS as $nome => $cfgNav): ?>
            <a href="crud.php?t=<?= h($nome) ?>" class="<?= $tabelaAtual === $nome ? 'ativo' : '' ?>"><?= h($cfgNav['titulo']) ?></a>
        <?php endforeach; ?>
        <a href="relatorios.php">Relatórios</a>
    </nav>
</header>
<main>
<?php if ($msgExibir): ?>
    <div class="msg <?= h($msgExibir[0]) ?>"><?= h($msgExibir[1]) ?></div>
<?php endif; ?>
