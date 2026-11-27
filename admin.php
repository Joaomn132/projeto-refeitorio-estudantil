<?php
// Painel administrativo: métricas + atalhos de gerenciamento.
require 'config.php';
exigir_admin();
$titulo = 'Métricas';

// ---- Métricas gerais ----
$totAlunos   = (int)escalar($conn, "SELECT COUNT(*) FROM aluno");
$cartAtivas  = (int)escalar($conn, "SELECT COUNT(*) FROM carteirinha WHERE status='ATIVA'");
$contasAtivas= (int)escalar($conn, "SELECT COUNT(*) FROM conta WHERE status='ATIVA'");
$refServidas = (int)escalar($conn, "SELECT COUNT(*) FROM acesso");
$arrecadado  = (float)escalar($conn, "SELECT IFNULL(SUM(valor),0) FROM pagamento WHERE status='CONFIRMADO'");
$pendentes   = (int)escalar($conn, "SELECT COUNT(*) FROM pagamento WHERE status='PENDENTE'");
$saldoTotal  = (float)escalar($conn, "SELECT IFNULL(SUM(saldo),0) FROM vw_saldo");
$debitado    = (float)escalar($conn, "SELECT IFNULL(SUM(valor_debitado),0) FROM acesso");

$metricas = [
    ['Alunos cadastrados',    number_format($totAlunos, 0, ',', '.'), ''],
    ['Carteirinhas ativas',   number_format($cartAtivas, 0, ',', '.'), ''],
    ['Contas ativas',         number_format($contasAtivas, 0, ',', '.'), ''],
    ['Refeições servidas',    number_format($refServidas, 0, ',', '.'), ''],
    ['Total arrecadado',      reais($arrecadado), 'pos'],
    ['Total debitado',        reais($debitado), ''],
    ['Saldo total em contas', reais($saldoTotal), $saldoTotal < 0 ? 'neg' : 'pos'],
    ['Pagamentos pendentes',  number_format($pendentes, 0, ',', '.'), $pendentes > 0 ? 'avi' : ''],
];

$sv = 'viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"';
$ICONES = [
    'aluno'       => "<svg $sv><path d='M22 10v6'/><path d='M2 10l10-5 10 5-10 5z'/><path d='M6 12v5c3 3 9 3 12 0v-5'/></svg>",
    'carteirinha' => "<svg $sv><rect x='2' y='5' width='20' height='14' rx='2'/><circle cx='8' cy='11' r='2'/><path d='M14 10h4'/><path d='M14 14h4'/><path d='M5 16c.6-1.2 1.7-2 3-2s2.4.8 3 2'/></svg>",
    'conta'       => "<svg $sv><rect x='2' y='5' width='20' height='14' rx='2'/><path d='M2 10h20'/></svg>",
    'refeicao'    => "<svg $sv><path d='M3 2v7a2 2 0 0 0 2 2 2 2 0 0 0 2-2V2'/><path d='M5 11v11'/><path d='M12 2a3 3 0 0 0-3 3v6a2 2 0 0 0 2 2h2V2z'/><path d='M12 13v9'/><path d='M19 2a3 3 0 0 0-3 3v6h3'/><path d='M19 11v11'/></svg>",
    'pagamento'   => "<svg $sv><rect x='2' y='6' width='20' height='12' rx='2'/><circle cx='12' cy='12' r='2.5'/><path d='M6 12h.01'/><path d='M18 12h.01'/></svg>",
    'acesso'      => "<svg $sv><path d='M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4'/><path d='M10 17l5-5-5-5'/><path d='M15 12H3'/></svg>",
];

require 'header.php';
?>
<div class="card">
    <h2>Métricas</h2>
    <p>Visão geral do refeitório em tempo real.</p>
    <div class="metricas">
        <?php foreach ($metricas as [$rot, $val, $cor]): ?>
            <div class="bloco">
                <div class="rot"><?= h($rot) ?></div>
                <div class="val <?= h($cor) ?>"><?= h($val) ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="card">
    <h2>Gerenciar dados</h2>
    <div class="grade">
    <?php foreach ($TABELAS as $nome => $cfg):
        $total = $conn->query("SELECT COUNT(*) AS n FROM `$nome`")->fetch_assoc()['n']; ?>
        <a href="crud.php?t=<?= h($nome) ?>">
            <div class="icone" aria-hidden="true"><?= $ICONES[$nome] ?? '' ?></div>
            <strong><?= (int)$total ?></strong>
            <span class="rotulo"><?= h($cfg['titulo']) ?></span>
        </a>
    <?php endforeach; ?>
        <a href="relatorios.php">
            <div class="icone" aria-hidden="true"><svg <?= $sv ?>><path d="M3 3v18h18"/><rect x="7" y="12" width="3" height="6"/><rect x="12" y="8" width="3" height="10"/><rect x="17" y="5" width="3" height="13"/></svg></div>
            <strong>6</strong>
            <span class="rotulo">Relatórios</span>
        </a>
    </div>
</div>
<?php require 'footer.php'; ?>
