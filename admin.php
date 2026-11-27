<?php
// Painel administrativo: métricas, pagamentos pendentes e atalhos de gerenciamento.
require 'config.php';
exigir_admin();
$titulo = 'Métricas';

// Ação rápida para confirmar ou cancelar pagamento pendente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['op'] ?? '') === 'status_pagamento') {
    $idPag = isset($_POST['id']) && ctype_digit((string)$_POST['id']) ? (int)$_POST['id'] : 0;
    $novoStatus = $_POST['novo_status'] ?? '';
    if ($idPag && in_array($novoStatus, ['CONFIRMADO', 'CANCELADO'], true)) {
        try {
            $st = $conn->prepare("UPDATE pagamento SET status = ? WHERE id_pagamento = ?");
            $st->bind_param('si', $novoStatus, $idPag);
            $st->execute();
            flash('ok', $novoStatus === 'CONFIRMADO' ? 'Pagamento confirmado com sucesso.' : 'Pagamento cancelado.');
        } catch (mysqli_sql_exception $e) {
            flash('erro', mensagem_erro($e));
        }
    }
    header('Location: admin.php');
    exit;
}

// ---- Métricas gerais ----
$totAlunos   = (int)escalar($conn, "SELECT COUNT(*) FROM aluno");
$cartAtivas  = (int)escalar($conn, "SELECT COUNT(*) FROM carteirinha WHERE status='ATIVA'");
$contasAtivas= (int)escalar($conn, "SELECT COUNT(*) FROM conta WHERE status='ATIVA'");
$refServidas = (int)escalar($conn, "SELECT COUNT(*) FROM acesso");
$arrecadado  = (float)escalar($conn, "SELECT IFNULL(SUM(valor),0) FROM pagamento WHERE status='CONFIRMADO'");
$pendentes   = (int)escalar($conn, "SELECT COUNT(*) FROM pagamento WHERE status='PENDENTE'");
$saldoTotal  = (float)escalar($conn, "SELECT IFNULL(SUM(saldo),0) FROM vw_saldo");
$debitado    = (float)escalar($conn, "SELECT IFNULL(SUM(valor_debitado),0) FROM acesso");

$pagPendentes = [];
if ($pendentes > 0) {
    $pagPendentes = $conn->query(
        "SELECT p.id_pagamento, p.valor, p.data_hora, p.forma_pagamento, a.matricula, a.nome
           FROM pagamento p
           JOIN conta c ON c.id_conta = p.id_conta
           JOIN aluno a ON a.id_aluno = c.id_aluno
          WHERE p.status = 'PENDENTE'
          ORDER BY p.data_hora, p.id_pagamento"
    )->fetch_all(MYSQLI_ASSOC);
}

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

<?php if ($pagPendentes): ?>
<div class="card">
    <div class="card-topo">
        <h2>Pagamentos pendentes</h2>
        <span class="card-sub">Confirmação rápida de créditos</span>
    </div>
    <div class="tabela-wrap">
        <table>
            <tr><th>ID</th><th>Matrícula</th><th>Estudante</th><th>Data e hora</th><th>Forma</th><th>Valor</th><th>Ações</th></tr>
            <?php foreach ($pagPendentes as $p): ?>
                <tr>
                    <td><?= (int)$p['id_pagamento'] ?></td>
                    <td><?= h($p['matricula']) ?></td>
                    <td><?= h($p['nome']) ?></td>
                    <td><?= h(date('d/m/Y H:i', strtotime($p['data_hora']))) ?></td>
                    <td><?= h($p['forma_pagamento']) ?></td>
                    <td><?= h(reais($p['valor'])) ?></td>
                    <td class="acoes">
                        <form class="inline" method="post" action="admin.php">
                            <input type="hidden" name="op" value="status_pagamento">
                            <input type="hidden" name="id" value="<?= (int)$p['id_pagamento'] ?>">
                            <input type="hidden" name="novo_status" value="CONFIRMADO">
                            <button class="btn peq ok" type="submit">Confirmar</button>
                        </form>
                        <form class="inline" method="post" action="admin.php" onsubmit="return confirm('Cancelar este pagamento?');">
                            <input type="hidden" name="op" value="status_pagamento">
                            <input type="hidden" name="id" value="<?= (int)$p['id_pagamento'] ?>">
                            <input type="hidden" name="novo_status" value="CANCELADO">
                            <button class="btn peq perigo" type="submit">Cancelar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <h2>Gerenciar dados</h2>
    <div class="grade">
        <a href="catraca.php">
            <div class="icone" aria-hidden="true"><svg <?= $sv ?>><rect x='3' y='4' width='18' height='16' rx='2'/><path d='M7 12h10'/><path d='M13 8l4 4-4 4'/></svg></div>
            <strong>Catraca</strong>
            <span class="rotulo">Registrar acesso</span>
        </a>
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
