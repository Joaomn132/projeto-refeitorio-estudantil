<?php
// Portaria / Catraca (admin): registro rápido de acesso por carteirinha ou matrícula.
require 'config.php';
exigir_admin();
$titulo = 'Catraca';

$refeicoesLista = $conn->query(
    "SELECT id_refeicao, `data`, tipo, cardapio, preco
       FROM refeicao
      ORDER BY (`data` = CURDATE()) DESC, `data` DESC, tipo
      LIMIT 30"
)->fetch_all(MYSQLI_ASSOC);

$idRefeicao = 0;
if (isset($_POST['id_refeicao']) && ctype_digit((string)$_POST['id_refeicao'])) {
    $idRefeicao = (int)$_POST['id_refeicao'];
} elseif (isset($_GET['refeicao']) && ctype_digit((string)$_GET['refeicao'])) {
    $idRefeicao = (int)$_GET['refeicao'];
} elseif ($refeicoesLista) {
    $idRefeicao = (int)$refeicoesLista[0]['id_refeicao'];
}

$codigoDigitado = '';
$resultado = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigoDigitado = trim((string)($_POST['codigo'] ?? ''));

    if (!$idRefeicao) {
        $resultado = ['ok' => false, 'mensagem' => 'Selecione uma refeição antes de registrar o acesso.'];
    } elseif ($codigoDigitado === '') {
        $resultado = ['ok' => false, 'mensagem' => 'Informe o código da carteirinha ou a matrícula do estudante.'];
    } else {
        // 1) Tenta localizar pela carteirinha; 2) se não achar, localiza pela matrícula
        $sqlBase = "SELECT al.id_aluno, al.matricula, al.nome, al.curso,
                           ct.id_carteirinha, ct.codigo AS cart_codigo, ct.validade, ct.status AS cart_status,
                           co.status AS conta_status, s.saldo
                      FROM aluno al
                      LEFT JOIN carteirinha ct ON ct.id_aluno = al.id_aluno
                      LEFT JOIN conta co       ON co.id_aluno = al.id_aluno
                      LEFT JOIN vw_saldo s     ON s.id_conta = co.id_conta";

        $st = $conn->prepare($sqlBase . " WHERE ct.codigo = ? LIMIT 1");
        $st->bind_param('s', $codigoDigitado);
        $st->execute();
        $reg = $st->get_result()->fetch_assoc();

        if (!$reg) {
            $st = $conn->prepare(
                $sqlBase . " WHERE al.matricula = ?
                             ORDER BY (ct.status = 'ATIVA') DESC, ct.validade DESC, ct.id_carteirinha DESC
                             LIMIT 1"
            );
            $st->bind_param('s', $codigoDigitado);
            $st->execute();
            $reg = $st->get_result()->fetch_assoc();
        }

        if (!$reg) {
            $resultado = ['ok' => false, 'mensagem' => 'Nenhuma carteirinha ou matrícula encontrada para "' . $codigoDigitado . '".'];
        } elseif (!$reg['id_carteirinha']) {
            $resultado = [
                'ok' => false,
                'mensagem' => 'Estudante encontrado, mas não possui carteirinha cadastrada.',
                'aluno' => $reg,
            ];
        } else {
            try {
                // O gatilho trg_acesso_bi valida carteirinha, conta e saldo e define valor_debitado
                $st = $conn->prepare("INSERT INTO acesso (id_carteirinha, id_refeicao, valor_debitado) VALUES (?, ?, 1.00)");
                $idCart = (int)$reg['id_carteirinha'];
                $st->bind_param('ii', $idCart, $idRefeicao);
                $st->execute();
                $idAcesso = (int)$conn->insert_id;

                $st = $conn->prepare("SELECT valor_debitado FROM acesso WHERE id_acesso = ?");
                $st->bind_param('i', $idAcesso);
                $st->execute();
                $valorDebitado = $st->get_result()->fetch_assoc()['valor_debitado'] ?? 0;

                $st = $conn->prepare("SELECT saldo FROM vw_saldo WHERE id_aluno = ?");
                $idAl = (int)$reg['id_aluno'];
                $st->bind_param('i', $idAl);
                $st->execute();
                $reg['saldo'] = $st->get_result()->fetch_assoc()['saldo'] ?? $reg['saldo'];

                $resultado = [
                    'ok' => true,
                    'mensagem' => 'Acesso liberado e registrado com sucesso.',
                    'aluno' => $reg,
                    'valor_debitado' => $valorDebitado,
                ];
                $codigoDigitado = '';
            } catch (mysqli_sql_exception $e) {
                $resultado = [
                    'ok' => false,
                    'mensagem' => mensagem_erro($e),
                    'aluno' => $reg,
                ];
            }
        }
    }
}

$ultimosAcessos = $conn->query(
    "SELECT a.data_hora, al.matricula, al.nome, ct.codigo AS cart_codigo,
            r.`data` AS data_refeicao, r.tipo, a.valor_debitado
       FROM acesso a
       JOIN carteirinha ct ON ct.id_carteirinha = a.id_carteirinha
       JOIN aluno al       ON al.id_aluno = ct.id_aluno
       JOIN refeicao r     ON r.id_refeicao = a.id_refeicao
      ORDER BY a.data_hora DESC, a.id_acesso DESC
      LIMIT 10"
)->fetch_all(MYSQLI_ASSOC);

function rotulo_refeicao($r)
{
    $tipo = $r['tipo'] === 'ALMOCO' ? 'Almoço' : ($r['tipo'] === 'JANTAR' ? 'Jantar' : $r['tipo']);
    return date('d/m/Y', strtotime($r['data'])) . ' — ' . $tipo . ' (' . reais($r['preco']) . ') · ' . $r['cardapio'];
}

require 'header.php';
?>
<div class="card">
    <div class="card-topo">
        <h2>Catraca &mdash; Registrar acesso</h2>
        <span class="card-sub">Leitura rápida por código da carteirinha ou matrícula</span>
    </div>

    <?php if (!$refeicoesLista): ?>
        <div class="estado-vazio">
            <strong>Nenhuma refeição cadastrada</strong>
            Cadastre uma refeição em <a href="crud.php?t=refeicao&amp;a=novo">Refeições</a> antes de registrar acessos.
        </div>
    <?php else: ?>
        <form method="post" action="catraca.php">
            <div class="catraca-grid">
                <div>
                    <label for="id_refeicao">Refeição</label>
                    <select id="id_refeicao" name="id_refeicao" required>
                        <?php foreach ($refeicoesLista as $rf): ?>
                            <option value="<?= (int)$rf['id_refeicao'] ?>" <?= $idRefeicao === (int)$rf['id_refeicao'] ? 'selected' : '' ?>>
                                <?= h(rotulo_refeicao($rf)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="codigo">Carteirinha ou matrícula</label>
                    <input id="codigo" name="codigo" type="text" value="<?= h($codigoDigitado) ?>"
                           placeholder="Ex.: CART-0001 ou 20230001" autocomplete="off" autofocus required>
                </div>
            </div>
            <button class="btn" type="submit">Registrar entrada</button>
        </form>
    <?php endif; ?>

    <?php if ($resultado): ?>
        <div class="msg <?= $resultado['ok'] ? 'ok' : 'erro' ?>" style="margin-top:18px;margin-bottom:0">
            <strong><?= h($resultado['mensagem']) ?></strong>
            <?php if (!empty($resultado['aluno'])): $al = $resultado['aluno']; ?>
                <div style="margin-top:8px;font-size:.9rem">
                    <strong><?= h($al['nome']) ?></strong> (Mat. <?= h($al['matricula']) ?>)
                    <?php if (!empty($al['cart_codigo'])): ?>
                        &middot; Carteirinha <?= h($al['cart_codigo']) ?> (<?= h($al['cart_status']) ?>, val. <?= h(date('d/m/Y', strtotime($al['validade']))) ?>)
                    <?php endif; ?>
                    <?php if ($al['conta_status'] !== null): ?>
                        &middot; Conta <?= h($al['conta_status']) ?>
                    <?php endif; ?>
                    <?php if ($al['saldo'] !== null): ?>
                        &middot; Saldo <?= $resultado['ok'] ? 'atualizado' : 'disponível' ?>: <strong><?= h(reais($al['saldo'])) ?></strong>
                    <?php endif; ?>
                    <?php if ($resultado['ok'] && isset($resultado['valor_debitado'])): ?>
                        &middot; Debitado: <strong><?= h(reais($resultado['valor_debitado'])) ?></strong>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-topo">
        <h2>Últimos acessos registrados</h2>
        <a class="btn sec peq" href="crud.php?t=acesso">Ver todos</a>
    </div>
    <?php if (!$ultimosAcessos): ?>
        <div class="estado-vazio">Nenhum acesso registrado ainda.</div>
    <?php else: ?>
        <div class="tabela-wrap">
            <table>
                <tr><th>Data e hora</th><th>Matrícula</th><th>Estudante</th><th>Carteirinha</th><th>Refeição</th><th>Valor</th></tr>
                <?php foreach ($ultimosAcessos as $ac): ?>
                    <tr>
                        <td><?= h(date('d/m/Y H:i', strtotime($ac['data_hora']))) ?></td>
                        <td><?= h($ac['matricula']) ?></td>
                        <td><?= h($ac['nome']) ?></td>
                        <td><?= h($ac['cart_codigo']) ?></td>
                        <td><?= h(date('d/m/Y', strtotime($ac['data_refeicao']))) ?> <?= h($ac['tipo'] === 'ALMOCO' ? 'Almoço' : 'Jantar') ?></td>
                        <td><?= h(reais($ac['valor_debitado'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php require 'footer.php'; ?>
