<?php
// Área do estudante (pública): cardápio, saldo e últimas refeições.
require 'config.php';
$titulo = 'Início';

// Aluno selecionado para consultar a conta
$idAluno = isset($_GET['aluno']) && ctype_digit((string)$_GET['aluno']) ? (int)$_GET['aluno'] : 0;

// Lista de alunos para o seletor
$alunos = $conn->query("SELECT id_aluno, matricula, nome FROM aluno ORDER BY nome")->fetch_all(MYSQLI_ASSOC);

// Cardápio: próximas refeições; se não houver futuras, mostra as mais recentes
$cardapio = $conn->query(
    "SELECT `data`, tipo, cardapio, preco FROM refeicao WHERE `data` >= CURDATE() ORDER BY `data`, tipo LIMIT 8"
)->fetch_all(MYSQLI_ASSOC);
$cardapioFuturo = !empty($cardapio);
if (!$cardapio) {
    $cardapio = $conn->query(
        "SELECT `data`, tipo, cardapio, preco FROM refeicao ORDER BY `data` DESC, tipo DESC LIMIT 8"
    )->fetch_all(MYSQLI_ASSOC);
}

// Dados da conta do aluno selecionado
$conta = null; $refeicoes = [];
if ($idAluno) {
    $st = $conn->prepare(
        "SELECT a.nome, c.status, s.saldo
           FROM aluno a
           LEFT JOIN conta c    ON c.id_aluno = a.id_aluno
           LEFT JOIN vw_saldo s ON s.id_conta = c.id_conta
          WHERE a.id_aluno = ?"
    );
    $st->bind_param('i', $idAluno);
    $st->execute();
    $conta = $st->get_result()->fetch_assoc();

    $st = $conn->prepare(
        "SELECT r.`data`, r.tipo, r.cardapio, a.valor_debitado, a.data_hora
           FROM acesso a
           JOIN carteirinha ct ON ct.id_carteirinha = a.id_carteirinha
           JOIN refeicao r     ON r.id_refeicao = a.id_refeicao
          WHERE ct.id_aluno = ?
          ORDER BY a.data_hora DESC
          LIMIT 10"
    );
    $st->bind_param('i', $idAluno);
    $st->execute();
    $refeicoes = $st->get_result()->fetch_all(MYSQLI_ASSOC);
}

function rotulo_tipo($t) { return $t === 'ALMOCO' ? 'Almoço' : ($t === 'JANTAR' ? 'Jantar' : $t); }

require 'header.php';
?>
<div class="card">
    <h2>Cardápio <?= $cardapioFuturo ? 'das próximas refeições' : '(últimas registradas)' ?></h2>
    <?php if (!$cardapio): ?>
        <p class="vazio">Nenhuma refeição cadastrada ainda.</p>
    <?php else: ?>
        <div class="cardapio">
            <?php foreach ($cardapio as $r): ?>
                <div class="prato">
                    <div class="prato-topo">
                        <span class="tag <?= $r['tipo'] === 'ALMOCO' ? 'tag-almoco' : 'tag-jantar' ?>"><?= h(rotulo_tipo($r['tipo'])) ?></span>
                        <span class="prato-data"><?= h(date('d/m/Y', strtotime($r['data']))) ?></span>
                    </div>
                    <div class="prato-nome"><?= h($r['cardapio']) ?></div>
                    <div class="prato-preco"><?= h(reais($r['preco'])) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Minha conta</h2>
    <form method="get" class="seletor">
        <label for="aluno">Selecione o estudante</label>
        <div class="seletor-linha">
            <select id="aluno" name="aluno" onchange="this.form.submit()">
                <option value="">— escolha seu nome —</option>
                <?php foreach ($alunos as $a): ?>
                    <option value="<?= (int)$a['id_aluno'] ?>" <?= $idAluno === (int)$a['id_aluno'] ? 'selected' : '' ?>>
                        <?= h($a['nome']) ?> (<?= h($a['matricula']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <noscript><button class="btn" type="submit">Ver</button></noscript>
        </div>
    </form>

    <?php if ($idAluno && $conta): ?>
        <div class="stat">
            <div class="bloco">
                <div class="rot">Situação da conta</div>
                <div class="val" style="font-size:1.2rem;margin-top:8px">
                    <?php if ($conta['status'] === null): ?>
                        <span class="badge neu">Sem conta</span>
                    <?php else: ?>
                        <?= badge_status($conta['status']) ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="bloco">
                <div class="rot">Saldo disponível</div>
                <?php $saldo = $conta['saldo']; ?>
                <div class="val <?= $saldo === null ? '' : ($saldo < 0 ? 'neg' : 'pos') ?>">
                    <?= $saldo === null ? '—' : h(reais($saldo)) ?>
                </div>
            </div>
            <div class="bloco">
                <div class="rot">Refeições consumidas</div>
                <div class="val"><?= count($refeicoes) ?><?= count($refeicoes) === 10 ? '+' : '' ?></div>
            </div>
        </div>

        <h3 style="margin:24px 0 10px;font-size:1.02rem">Últimas refeições</h3>
        <?php if (!$refeicoes): ?>
            <p class="vazio">Este estudante ainda não registrou acessos ao refeitório.</p>
        <?php else: ?>
            <div class="tabela-wrap">
                <table>
                    <tr><th>Data do acesso</th><th>Refeição</th><th>Tipo</th><th>Cardápio</th><th>Valor</th></tr>
                    <?php foreach ($refeicoes as $r): ?>
                        <tr>
                            <td><?= h(date('d/m/Y H:i', strtotime($r['data_hora']))) ?></td>
                            <td><?= h(date('d/m/Y', strtotime($r['data']))) ?></td>
                            <td><?= h(rotulo_tipo($r['tipo'])) ?></td>
                            <td><?= h($r['cardapio']) ?></td>
                            <td><?= h(reais($r['valor_debitado'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endif; ?>
    <?php elseif ($idAluno && !$conta): ?>
        <p class="vazio">Estudante não encontrado.</p>
    <?php else: ?>
        <p class="vazio">Escolha seu nome acima para ver o saldo e as últimas refeições.</p>
    <?php endif; ?>
</div>
<?php require 'footer.php'; ?>
