<?php
// CRUD genérico: serve as 6 tabelas, a partir da descrição em config.php.
// Uso: crud.php?t=aluno  (listar) | &a=novo | &a=editar&id=3
require 'config.php';
exigir_admin();

$t = $_GET['t'] ?? $_POST['t'] ?? '';
if (!isset($TABELAS[$t])) {
    header('Location: index.php');
    exit;
}
$cfg = $TABELAS[$t];
$pk = $cfg['pk'];
$campos = $cfg['campos'];
$acao = $_GET['a'] ?? 'listar';
$busca = trim((string)($_GET['q'] ?? $_POST['q'] ?? ''));
$pag = isset($_GET['pag']) && ctype_digit((string)$_GET['pag']) ? max(1, (int)$_GET['pag']) : 1;
$msg = null;
$valoresForm = [];
$idForm = $_GET['id'] ?? '';

function url_crud($t, $q = '', $pag = 1)
{
    $params = ['t' => $t];
    if ($q !== '') {
        $params['q'] = $q;
    }
    if ($pag > 1) {
        $params['pag'] = $pag;
    }
    return 'crud.php?' . http_build_query($params);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $op = $_POST['op'] ?? '';
    try {
        if ($op === 'excluir') {
            $id = $_POST['id'] ?? '';
            $st = $conn->prepare("DELETE FROM `$t` WHERE `$pk` = ?");
            $st->bind_param('s', $id);
            $st->execute();
            flash('ok', 'Registro excluído com sucesso.');
            header('Location: ' . url_crud($t, $busca, $pag));
            exit;
        }

        if ($op === 'status_pagamento' && $t === 'pagamento') {
            $id = $_POST['id'] ?? '';
            $novoStatus = $_POST['novo_status'] ?? '';
            if (in_array($novoStatus, ['CONFIRMADO', 'CANCELADO'], true)) {
                $st = $conn->prepare("UPDATE pagamento SET status = ? WHERE id_pagamento = ?");
                $st->bind_param('ss', $novoStatus, $id);
                $st->execute();
                flash('ok', $novoStatus === 'CONFIRMADO' ? 'Pagamento confirmado com sucesso.' : 'Pagamento cancelado.');
            }
            header('Location: ' . url_crud($t, $busca, $pag));
            exit;
        }

        if ($op === 'salvar') {
            $valores = [];
            foreach ($campos as $nome => $c) {
                $v = trim($_POST[$nome] ?? '');
                if ($v === '' && isset($c['padrao'])) {
                    $v = $c['padrao'];
                }
                if ($c['tipo'] === 'datetime' && $v !== '') {
                    $v = str_replace('T', ' ', $v);
                    if (strlen($v) === 16) {
                        $v .= ':00';
                    }
                }
                $valores[$nome] = $v;
            }

            $id = $_POST['id'] ?? '';
            $cols = array_keys($valores);
            $params = array_values($valores);

            if ($id === '') {
                $sql = "INSERT INTO `$t` (`" . implode('`, `', $cols) . "`) VALUES ("
                     . implode(', ', array_fill(0, count($cols), '?')) . ")";
            } else {
                $sets = array_map(fn($c) => "`$c` = ?", $cols);
                $sql = "UPDATE `$t` SET " . implode(', ', $sets) . " WHERE `$pk` = ?";
                $params[] = $id;
            }

            // Alinhado ao relacionamento 1:1 (ALUNO POSSUI CONTA): cria a conta ao cadastrar novo aluno
            $criouConta = false;
            if ($id === '' && $t === 'aluno') {
                $conn->begin_transaction();
                try {
                    $st = $conn->prepare($sql);
                    $st->bind_param(str_repeat('s', count($params)), ...$params);
                    $st->execute();

                    $novoAlunoId = (int)$conn->insert_id;
                    $stConta = $conn->prepare("INSERT INTO conta (id_aluno, status) VALUES (?, 'ATIVA')");
                    $stConta->bind_param('i', $novoAlunoId);
                    $stConta->execute();

                    $conn->commit();
                    $criouConta = true;
                } catch (Throwable $e) {
                    $conn->rollback();
                    throw $e;
                }
            } else {
                $st = $conn->prepare($sql);
                $st->bind_param(str_repeat('s', count($params)), ...$params);
                $st->execute();
            }

            flash('ok', $id === ''
                ? ($criouConta ? 'Aluno incluído e conta criada automaticamente.' : 'Registro incluído com sucesso.')
                : 'Registro alterado com sucesso.');
            header('Location: crud.php?t=' . urlencode($t));
            exit;
        }
    } catch (mysqli_sql_exception $e) {
        $msg = ['erro', mensagem_erro($e)];
        if ($op === 'salvar') {
            $idForm = $_POST['id'] ?? '';
            $valoresForm = $_POST;
            $acao = $idForm === '' ? 'novo' : 'editar';
        }
    }
}

// Opções dos campos de chave estrangeira e de lista fixa
$opcoes = [];
foreach ($campos as $nome => $c) {
    if ($c['tipo'] === 'fk' || $c['tipo'] === 'select') {
        $opcoes[$nome] = carregar_opcoes($conn, $c);
    }
}

// Ao editar (sem erro de envio), carrega o registro do banco
if ($acao === 'editar' && !$valoresForm) {
    $st = $conn->prepare("SELECT * FROM `$t` WHERE `$pk` = ?");
    $st->bind_param('s', $idForm);
    $st->execute();
    $linha = $st->get_result()->fetch_assoc();
    if (!$linha) {
        flash('erro', 'Registro não encontrado.');
        header('Location: crud.php?t=' . urlencode($t));
        exit;
    }
    foreach ($campos as $nome => $c) {
        $v = $linha[$nome];
        if ($c['tipo'] === 'datetime' && $v !== null) {
            $v = str_replace(' ', 'T', substr($v, 0, 16));
        }
        $valoresForm[$nome] = $v;
    }
}

$titulo = $cfg['titulo'];
require 'header.php';

if ($acao === 'novo' || $acao === 'editar'):
    $editando = ($acao === 'editar');
?>
<div class="card">
    <h2><?= $editando ? 'Alterar' : 'Incluir' ?> <?= h($cfg['singular']) ?></h2>
    <form method="post" action="crud.php?t=<?= h($t) ?>">
        <input type="hidden" name="op" value="salvar">
        <input type="hidden" name="t" value="<?= h($t) ?>">
        <input type="hidden" name="id" value="<?= $editando ? h($idForm) : '' ?>">

        <?php foreach ($campos as $nome => $c):
            $v = $valoresForm[$nome] ?? '';
            if ($v === '' && $c['tipo'] === 'datetime' && !$editando) {
                $v = date('Y-m-d\TH:i');
            }
            $req = (($c['obrigatorio'] ?? true) === false) ? '' : 'required';
        ?>
            <label for="<?= h($nome) ?>"><?= h($c['rotulo']) ?>
                <?php if (!empty($c['dica'])): ?><small>(<?= h($c['dica']) ?>)</small><?php endif; ?>
            </label>
            <?php if ($c['tipo'] === 'fk' || $c['tipo'] === 'select'): ?>
                <select id="<?= h($nome) ?>" name="<?= h($nome) ?>" <?= $req ?>>
                    <option value="">Selecione...</option>
                    <?php foreach ($opcoes[$nome] as $valor => $rotulo): ?>
                        <option value="<?= h($valor) ?>" <?= ((string)$v === (string)$valor) ? 'selected' : '' ?>><?= h($rotulo) ?></option>
                    <?php endforeach; ?>
                </select>
            <?php elseif ($c['tipo'] === 'number'): ?>
                <input id="<?= h($nome) ?>" name="<?= h($nome) ?>" type="number" step="0.01" min="0.01" value="<?= h($v) ?>" <?= $req ?>>
            <?php elseif ($c['tipo'] === 'date'): ?>
                <input id="<?= h($nome) ?>" name="<?= h($nome) ?>" type="date" value="<?= h($v) ?>" <?= $req ?>>
            <?php elseif ($c['tipo'] === 'datetime'): ?>
                <input id="<?= h($nome) ?>" name="<?= h($nome) ?>" type="datetime-local" step="60" value="<?= h($v) ?>" <?= $req ?>>
            <?php else: ?>
                <input id="<?= h($nome) ?>" name="<?= h($nome) ?>" type="<?= $c['tipo'] === 'email' ? 'email' : 'text' ?>"
                       maxlength="<?= (int)($c['max'] ?? 100) ?>" value="<?= h($v) ?>" <?= $req ?>>
            <?php endif; ?>
        <?php endforeach; ?>

        <p style="margin-top:18px">
            <button class="btn" type="submit">Salvar</button>
            <a class="btn sec" href="crud.php?t=<?= h($t) ?>">Cancelar</a>
        </p>
    </form>
</div>

<?php else:
    if ($t === 'conta') {
        $sqlLista = "SELECT c.*, s.saldo FROM conta c LEFT JOIN vw_saldo s ON s.id_conta = c.id_conta ORDER BY c.id_conta";
    } else {
        $sqlLista = "SELECT * FROM `$t` ORDER BY `$pk`";
    }
    $linhas = $conn->query($sqlLista)->fetch_all(MYSQLI_ASSOC);

    if ($busca !== '') {
        $linhas = array_values(array_filter($linhas, function ($linha) use ($pk, $campos, $opcoes, $busca, $t) {
            if (mb_stripos((string)$linha[$pk], $busca) !== false) {
                return true;
            }
            foreach ($campos as $nome => $c) {
                $texto = (string)formatar($c, $linha[$nome], $opcoes[$nome] ?? []);
                if (mb_stripos($texto, $busca) !== false) {
                    return true;
                }
            }
            if ($t === 'conta' && $linha['saldo'] !== null && mb_stripos(reais($linha['saldo']), $busca) !== false) {
                return true;
            }
            return false;
        }));
    }

    $totalRegistros = count($linhas);
    $porPagina = 15;
    $totalPaginas = max(1, (int)ceil($totalRegistros / $porPagina));
    if ($pag > $totalPaginas) {
        $pag = $totalPaginas;
    }
    $offset = ($pag - 1) * $porPagina;
    $linhasPagina = array_slice($linhas, $offset, $porPagina);
    $colSpan = count($campos) + 2 + ($t === 'conta' ? 1 : 0);
?>
<div class="card">
    <h2><?= h($cfg['titulo']) ?></h2>
    <div class="barra-crud">
        <a class="btn" href="crud.php?t=<?= h($t) ?>&a=novo">+ Incluir <?= h($cfg['singular']) ?></a>
        <form method="get" action="crud.php" class="busca-crud">
            <input type="hidden" name="t" value="<?= h($t) ?>">
            <input type="text" name="q" value="<?= h($busca) ?>" placeholder="Filtrar <?= h(mb_strtolower($cfg['titulo'])) ?>..." aria-label="Filtrar registros">
            <button class="btn sec" type="submit">Filtrar</button>
            <?php if ($busca !== ''): ?>
                <a class="btn sec" href="crud.php?t=<?= h($t) ?>">Limpar</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="tabela-wrap">
    <table>
        <tr>
            <th>ID</th>
            <?php foreach ($campos as $c): ?><th><?= h($c['rotulo']) ?></th><?php endforeach; ?>
            <?php if ($t === 'conta'): ?><th>Saldo</th><?php endif; ?>
            <th>Ações</th>
        </tr>
        <?php foreach ($linhasPagina as $linha): ?>
            <tr>
                <td><?= h($linha[$pk]) ?></td>
                <?php foreach ($campos as $nome => $c): ?>
                    <?php if ($c['tipo'] === 'select' && strtolower($c['rotulo']) === 'status' && $linha[$nome] !== null): ?>
                        <td><?= badge_status($linha[$nome]) ?></td>
                    <?php else: ?>
                        <td><?= h(formatar($c, $linha[$nome], $opcoes[$nome] ?? [])) ?></td>
                    <?php endif; ?>
                <?php endforeach; ?>
                <?php if ($t === 'conta'):
                    $s = $linha['saldo']; ?>
                    <td class="<?= $s === null ? '' : ($s < 0 ? 'valor-neg' : 'valor-pos') ?>">
                        <?= $s === null ? '—' : h(reais($s)) ?>
                    </td>
                <?php endif; ?>
                <td class="acoes">
                    <?php if ($t === 'pagamento' && $linha['status'] === 'PENDENTE'): ?>
                        <form class="inline" method="post" action="<?= h(url_crud($t, $busca, $pag)) ?>">
                            <input type="hidden" name="op" value="status_pagamento">
                            <input type="hidden" name="t" value="<?= h($t) ?>">
                            <input type="hidden" name="id" value="<?= h($linha[$pk]) ?>">
                            <input type="hidden" name="novo_status" value="CONFIRMADO">
                            <button class="btn peq ok" type="submit">Confirmar</button>
                        </form>
                        <form class="inline" method="post" action="<?= h(url_crud($t, $busca, $pag)) ?>"
                              onsubmit="return confirm('Cancelar este pagamento?');">
                            <input type="hidden" name="op" value="status_pagamento">
                            <input type="hidden" name="t" value="<?= h($t) ?>">
                            <input type="hidden" name="id" value="<?= h($linha[$pk]) ?>">
                            <input type="hidden" name="novo_status" value="CANCELADO">
                            <button class="btn peq perigo" type="submit">Cancelar</button>
                        </form>
                    <?php endif; ?>
                    <a class="btn peq" href="crud.php?t=<?= h($t) ?>&a=editar&id=<?= h($linha[$pk]) ?>">Alterar</a>
                    <form class="inline" method="post" action="<?= h(url_crud($t, $busca, $pag)) ?>"
                          onsubmit="return confirm('Excluir este registro?');">
                        <input type="hidden" name="op" value="excluir">
                        <input type="hidden" name="t" value="<?= h($t) ?>">
                        <input type="hidden" name="id" value="<?= h($linha[$pk]) ?>">
                        <button class="btn peq perigo" type="submit">Excluir</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$linhasPagina): ?>
            <tr><td colspan="<?= $colSpan ?>"><?= $busca !== '' ? 'Nenhum registro encontrado para "' . h($busca) . '".' : 'Nenhum registro cadastrado.' ?></td></tr>
        <?php endif; ?>
    </table>
    </div>

    <?php if ($totalRegistros > 0): ?>
        <div class="paginacao">
            <span>
                Mostrando <?= $offset + 1 ?>&ndash;<?= min($offset + $porPagina, $totalRegistros) ?> de <?= $totalRegistros ?>
                <?= $busca !== '' ? 'resultado(s) filtrado(s)' : 'registro(s)' ?>
            </span>
            <?php if ($totalPaginas > 1): ?>
                <div class="paginacao-botoes">
                    <?php if ($pag > 1): ?>
                        <a class="btn sec peq" href="<?= h(url_crud($t, $busca, $pag - 1)) ?>">&larr; Anterior</a>
                    <?php endif; ?>
                    <span>Página <?= $pag ?> de <?= $totalPaginas ?></span>
                    <?php if ($pag < $totalPaginas): ?>
                        <a class="btn sec peq" href="<?= h(url_crud($t, $busca, $pag + 1)) ?>">Próxima &rarr;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php require 'footer.php'; ?>
