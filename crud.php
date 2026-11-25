<?php
// CRUD genérico: serve as 6 tabelas, a partir da descrição em config.php.
// Uso: crud.php?t=aluno  (listar) | &a=novo | &a=editar&id=3
require 'config.php';

$t = $_GET['t'] ?? $_POST['t'] ?? '';
if (!isset($TABELAS[$t])) {
    header('Location: index.php');
    exit;
}
$cfg = $TABELAS[$t];
$pk = $cfg['pk'];
$campos = $cfg['campos'];
$acao = $_GET['a'] ?? 'listar';
$msg = null;
$valoresForm = [];
$idForm = $_GET['id'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $op = $_POST['op'] ?? '';
    try {
        if ($op === 'excluir') {
            $id = $_POST['id'] ?? '';
            $st = $conn->prepare("DELETE FROM `$t` WHERE `$pk` = ?");
            $st->bind_param('s', $id);
            $st->execute();
            flash('ok', 'Registro excluído com sucesso.');
            header('Location: crud.php?t=' . urlencode($t));
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
            $st = $conn->prepare($sql);
            $st->bind_param(str_repeat('s', count($params)), ...$params);
            $st->execute();

            flash('ok', $id === '' ? 'Registro incluído com sucesso.' : 'Registro alterado com sucesso.');
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
    $res = $conn->query("SELECT * FROM `$t` ORDER BY `$pk`");
?>
<div class="card">
    <h2><?= h($cfg['titulo']) ?></h2>
    <p><a class="btn" href="crud.php?t=<?= h($t) ?>&a=novo">+ Incluir <?= h($cfg['singular']) ?></a></p>
    <div class="tabela-wrap">
    <table>
        <tr>
            <th>ID</th>
            <?php foreach ($campos as $c): ?><th><?= h($c['rotulo']) ?></th><?php endforeach; ?>
            <th>Ações</th>
        </tr>
        <?php $vazio = true; while ($linha = $res->fetch_assoc()): $vazio = false; ?>
            <tr>
                <td><?= h($linha[$pk]) ?></td>
                <?php foreach ($campos as $nome => $c): ?>
                    <td><?= h(formatar($c, $linha[$nome], $opcoes[$nome] ?? [])) ?></td>
                <?php endforeach; ?>
                <td class="acoes">
                    <a class="btn peq" href="crud.php?t=<?= h($t) ?>&a=editar&id=<?= h($linha[$pk]) ?>">Alterar</a>
                    <form class="inline" method="post" action="crud.php?t=<?= h($t) ?>"
                          onsubmit="return confirm('Excluir este registro?');">
                        <input type="hidden" name="op" value="excluir">
                        <input type="hidden" name="t" value="<?= h($t) ?>">
                        <input type="hidden" name="id" value="<?= h($linha[$pk]) ?>">
                        <button class="btn peq perigo" type="submit">Excluir</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
        <?php if ($vazio): ?>
            <tr><td colspan="<?= count($campos) + 2 ?>">Nenhum registro cadastrado.</td></tr>
        <?php endif; ?>
    </table>
    </div>
</div>
<?php endif; ?>

<?php require 'footer.php'; ?>
