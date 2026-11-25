<?php
require 'config.php';
$titulo = 'Início';
require 'header.php';
?>
<div class="card">
    <h2>Banco de dados do Refeitório Estudantil</h2>
    <p>Cadastro de alunos, carteirinhas, contas, refeições, pagamentos e acessos ao refeitório.
       Escolha uma tabela para incluir, alterar ou excluir registros, ou abra os relatórios de consulta.</p>
</div>

<div class="grade">
<?php foreach ($TABELAS as $nome => $cfg):
    $total = $conn->query("SELECT COUNT(*) AS n FROM `$nome`")->fetch_assoc()['n']; ?>
    <a href="crud.php?t=<?= h($nome) ?>">
        <strong><?= (int)$total ?></strong>
        <?= h($cfg['titulo']) ?>
    </a>
<?php endforeach; ?>
    <a href="relatorios.php">
        <strong>6</strong>
        Relatórios
    </a>
</div>
<?php require 'footer.php'; ?>
