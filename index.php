<?php
// Área do estudante (pública): cardápio, saldo, carteirinha, pagamentos e últimas refeições.
require 'config.php';
$titulo = 'Início';

// Parâmetros de consulta: seleção direta (?aluno=ID) ou busca por matrícula/nome (?q=...)
$idAluno = isset($_GET['aluno']) && ctype_digit((string)$_GET['aluno']) ? (int)$_GET['aluno'] : 0;
$busca   = trim((string)($_GET['q'] ?? ''));

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

// Busca estudante por matrícula ou nome (sem expor a lista completa)
$resultados = [];
if (!$idAluno && $busca !== '') {
    $st = $conn->prepare("SELECT id_aluno FROM aluno WHERE matricula = ? LIMIT 1");
    $st->bind_param('s', $busca);
    $st->execute();
    $exato = $st->get_result()->fetch_assoc();

    if ($exato) {
        $idAluno = (int)$exato['id_aluno'];
    } else {
        $termoEscapado = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $busca);
        $like = '%' . $termoEscapado . '%';
        $st = $conn->prepare(
            "SELECT id_aluno, matricula, nome, curso
               FROM aluno
              WHERE matricula LIKE ? ESCAPE '!' OR nome LIKE ? ESCAPE '!'
              ORDER BY nome
              LIMIT 20"
        );
        $st->bind_param('ss', $like, $like);
        $st->execute();
        $resultados = $st->get_result()->fetch_all(MYSQLI_ASSOC);

        if (count($resultados) === 1) {
            $idAluno = (int)$resultados[0]['id_aluno'];
            $resultados = [];
        }
    }
}

// Dados da conta, carteirinha, refeições e pagamentos do aluno selecionado
$conta = null;
$carteirinha = null;
$refeicoes = [];
$pagamentos = [];
if ($idAluno) {
    $st = $conn->prepare(
        "SELECT a.matricula, a.nome, a.curso, c.status, s.saldo
           FROM aluno a
           LEFT JOIN conta c    ON c.id_aluno = a.id_aluno
           LEFT JOIN vw_saldo s ON s.id_conta = c.id_conta
          WHERE a.id_aluno = ?"
    );
    $st->bind_param('i', $idAluno);
    $st->execute();
    $conta = $st->get_result()->fetch_assoc();

    if ($conta) {
        $st = $conn->prepare(
            "SELECT codigo, validade, status
               FROM carteirinha
              WHERE id_aluno = ?
              ORDER BY (status = 'ATIVA') DESC, validade DESC, id_carteirinha DESC
              LIMIT 1"
        );
        $st->bind_param('i', $idAluno);
        $st->execute();
        $carteirinha = $st->get_result()->fetch_assoc();

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

        $st = $conn->prepare(
            "SELECT p.valor, p.data_hora, p.forma_pagamento, p.status
               FROM pagamento p
               JOIN conta c ON c.id_conta = p.id_conta
              WHERE c.id_aluno = ?
              ORDER BY p.data_hora DESC, p.id_pagamento DESC
              LIMIT 10"
        );
        $st->bind_param('i', $idAluno);
        $st->execute();
        $pagamentos = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

function rotulo_tipo($t)
{
    return $t === 'ALMOCO' ? 'Almoço' : ($t === 'JANTAR' ? 'Jantar' : $t);
}

function rotulo_forma($f)
{
    return $f === 'CARTAO' ? 'Cartão' : ($f === 'DINHEIRO' ? 'Dinheiro' : $f);
}

function data_cardapio($data)
{
    $dias = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
    $ts = strtotime($data);
    return $dias[(int)date('w', $ts)] . ', ' . date('d/m/Y', $ts);
}

function iniciais_nome($nome)
{
    $partes = preg_split('/\s+/', trim((string)$nome));
    if (!$partes || $partes[0] === '') {
        return '?';
    }
    $ini = mb_substr($partes[0], 0, 1);
    if (count($partes) > 1) {
        $ini .= mb_substr($partes[count($partes) - 1], 0, 1);
    }
    return mb_strtoupper($ini);
}

require 'header.php';
?>
<div class="card">
    <div class="card-topo">
        <h2>Cardápio <?= $cardapioFuturo ? 'das próximas refeições' : 'recente' ?></h2>
        <span class="card-sub"><?= $cardapioFuturo ? 'Programação atual' : 'Últimas refeições registradas' ?></span>
    </div>
    <?php if (!$cardapio): ?>
        <div class="estado-vazio">Nenhuma refeição cadastrada ainda.</div>
    <?php else: ?>
        <div class="cardapio">
            <?php foreach ($cardapio as $r): ?>
                <div class="prato">
                    <div class="prato-topo">
                        <span class="tag <?= $r['tipo'] === 'ALMOCO' ? 'tag-almoco' : 'tag-jantar' ?>"><?= h(rotulo_tipo($r['tipo'])) ?></span>
                        <span class="prato-data"><?= h(data_cardapio($r['data'])) ?></span>
                    </div>
                    <div class="prato-nome"><?= h($r['cardapio']) ?></div>
                    <div class="prato-preco"><?= h(reais($r['preco'])) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-topo">
        <h2>Minha conta</h2>
        <span class="card-sub">Consulte seu saldo, carteirinha, pagamentos e refeições</span>
    </div>
    <form method="get" class="busca-form">
        <label for="q">Buscar por código de matrícula ou nome</label>
        <div class="busca-linha">
            <div class="busca-campo">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <input id="q" name="q" type="text" value="<?= h($busca) ?>"
                       placeholder="Ex.: 20230001 ou Ana Beatriz" autocomplete="off" required>
            </div>
            <div class="busca-acoes">
                <button class="btn" type="submit">Buscar</button>
                <?php if ($busca !== '' || $idAluno): ?>
                    <a class="btn sec" href="index.php">Limpar</a>
                <?php endif; ?>
            </div>
        </div>
    </form>

    <?php if ($idAluno && $conta): ?>
        <div class="aluno-perfil">
            <div class="aluno-avatar" aria-hidden="true"><?= h(iniciais_nome($conta['nome'])) ?></div>
            <div class="aluno-meta">
                <strong><?= h($conta['nome']) ?></strong>
                <span>Matrícula <?= h($conta['matricula']) ?> &middot; <?= h($conta['curso']) ?></span>
            </div>
        </div>

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
                <div class="rot">Carteirinha</div>
                <div class="val" style="font-size:1.2rem;margin-top:8px">
                    <?php if (!$carteirinha): ?>
                        <span class="badge neu">Sem carteirinha</span>
                    <?php else: ?>
                        <?= badge_status($carteirinha['status']) ?>
                    <?php endif; ?>
                </div>
                <?php if ($carteirinha):
                    $vencida = strtotime($carteirinha['validade']) < strtotime(date('Y-m-d')); ?>
                    <div class="carteirinha-meta">
                        <?= h($carteirinha['codigo']) ?> &middot;
                        Val. <?= h(date('d/m/Y', strtotime($carteirinha['validade']))) ?>
                        <?php if ($vencida): ?><span class="vencida">(Vencida)</span><?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="bloco">
                <div class="rot">Refeições consumidas</div>
                <div class="val"><?= count($refeicoes) ?><?= count($refeicoes) === 10 ? '+' : '' ?></div>
            </div>
        </div>

        <h3 class="secao-titulo">Últimas refeições</h3>
        <?php if (!$refeicoes): ?>
            <div class="estado-vazio">Este estudante ainda não registrou acessos ao refeitório.</div>
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

        <h3 class="secao-titulo">Últimos pagamentos</h3>
        <?php if (!$pagamentos): ?>
            <div class="estado-vazio">Nenhum pagamento registrado para esta conta.</div>
        <?php else: ?>
            <div class="tabela-wrap">
                <table>
                    <tr><th>Data e hora</th><th>Forma</th><th>Status</th><th>Valor</th></tr>
                    <?php foreach ($pagamentos as $p): ?>
                        <tr>
                            <td><?= h(date('d/m/Y H:i', strtotime($p['data_hora']))) ?></td>
                            <td><?= h(rotulo_forma($p['forma_pagamento'])) ?></td>
                            <td><?= badge_status($p['status']) ?></td>
                            <td><?= h(reais($p['valor'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endif; ?>
    <?php elseif ($resultados): ?>
        <p class="resultados-titulo">Encontramos <?= count($resultados) ?> estudantes para <strong>"<?= h($busca) ?>"</strong>. Selecione o seu cadastro:</p>
        <div class="resultados-lista">
            <?php foreach ($resultados as $r): ?>
                <a class="resultado-item" href="index.php?aluno=<?= (int)$r['id_aluno'] ?>&amp;q=<?= urlencode($busca) ?>">
                    <span class="aluno-avatar" aria-hidden="true"><?= h(iniciais_nome($r['nome'])) ?></span>
                    <span class="resultado-info">
                        <span class="resultado-nome" style="display:block"><?= h($r['nome']) ?></span>
                        <span class="resultado-det" style="display:block">Mat. <?= h($r['matricula']) ?> &middot; <?= h($r['curso']) ?></span>
                    </span>
                    <span class="resultado-seta" aria-hidden="true">Ver &rarr;</span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php elseif ($busca !== '' || $idAluno): ?>
        <div class="estado-vazio">
            <strong>Estudante não encontrado</strong>
            Verifique o código de matrícula ou o nome digitado e tente novamente.
        </div>
    <?php else: ?>
        <div class="estado-vazio">
            Informe seu código de matrícula ou nome acima para consultar a situação da conta, carteirinha, saldo disponível, pagamentos e últimas refeições.
        </div>
    <?php endif; ?>
</div>
<?php require 'footer.php'; ?>
