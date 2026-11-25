<?php
// Relatórios de consulta: cada um mostra o SQL usado e o resultado.
require 'config.php';
$titulo = 'Relatórios';

function relatorio($conn, $n, $titulo, $descricao, $sql, $params = [], $tipos = '', $extra = '')
{
    echo "<section class='card'>";
    echo "<h2>" . h($n) . ". " . h($titulo) . "</h2>";
    echo "<p>" . h($descricao) . "</p>";
    echo $extra;
    echo "<pre>" . h(trim($sql)) . "</pre>";
    try {
        $st = $conn->prepare($sql);
        if ($params) {
            $st->bind_param($tipos, ...$params);
        }
        $st->execute();
        $res = $st->get_result();
        $cols = $res->fetch_fields();

        echo "<div class='tabela-wrap'><table><tr>";
        foreach ($cols as $c) {
            echo "<th>" . h($c->name) . "</th>";
        }
        echo "</tr>";
        $vazio = true;
        while ($linha = $res->fetch_row()) {
            $vazio = false;
            echo "<tr>";
            foreach ($linha as $v) {
                echo "<td>" . ($v === null ? '—' : h($v)) . "</td>";
            }
            echo "</tr>";
        }
        if ($vazio) {
            echo "<tr><td colspan='" . count($cols) . "'>Nenhum resultado.</td></tr>";
        }
        echo "</table></div>";
    } catch (mysqli_sql_exception $e) {
        echo "<div class='msg erro'>" . h(mensagem_erro($e)) . "</div>";
    }
    echo "</section>";
}

require 'header.php';

// 1) Junção interna
relatorio($conn, 1, 'Acessos com aluno e refeição (junção interna)',
    'INNER JOIN de acesso, carteirinha, aluno e refeição: quem comeu o quê, e quanto foi debitado.',
    "SELECT al.nome AS aluno, ct.codigo AS carteirinha,
       r.`data` AS data_refeicao, r.tipo, r.cardapio, ac.valor_debitado
FROM acesso ac
JOIN carteirinha ct ON ct.id_carteirinha = ac.id_carteirinha
JOIN aluno al       ON al.id_aluno = ct.id_aluno
JOIN refeicao r     ON r.id_refeicao = ac.id_refeicao
ORDER BY r.`data`, r.tipo, al.nome");

// 2) Junção externa
relatorio($conn, 2, 'Alunos e seus acessos, inclusive quem nunca acessou (junção externa)',
    'LEFT JOIN de aluno com carteirinha e acesso: alunos sem acesso aparecem com campos vazios (—).',
    "SELECT al.matricula, al.nome, ct.codigo AS carteirinha,
       ac.data_hora AS data_acesso, ac.valor_debitado
FROM aluno al
LEFT JOIN carteirinha ct ON ct.id_aluno = al.id_aluno
LEFT JOIN acesso ac      ON ac.id_carteirinha = ct.id_carteirinha
ORDER BY al.nome, ac.data_hora");

// 3) Grupo
relatorio($conn, 3, 'Total gasto por aluno (GROUP BY)',
    'Agrupa os acessos por aluno (usando as junções externas do item anterior) e soma o valor debitado.',
    "SELECT al.nome, COUNT(ac.id_acesso) AS qtd_refeicoes,
       IFNULL(SUM(ac.valor_debitado), 0) AS total_gasto
FROM aluno al
LEFT JOIN carteirinha ct ON ct.id_aluno = al.id_aluno
LEFT JOIN acesso ac      ON ac.id_carteirinha = ct.id_carteirinha
GROUP BY al.id_aluno, al.nome
ORDER BY total_gasto DESC, al.nome");

// 4) HAVING (valor mínimo informado pelo usuário)
$minimo = (isset($_GET['minimo']) && is_numeric($_GET['minimo'])) ? (float)$_GET['minimo'] : 10;
$formMinimo = "<form method='get' style='margin-bottom:10px'>
    <label for='minimo'>Gasto mínimo (R$)</label>
    <input id='minimo' name='minimo' type='number' step='0.01' min='0' value='" . h($minimo) . "' style='max-width:160px'>
    <button class='btn' type='submit'>Filtrar</button></form>";
relatorio($conn, 4, 'Alunos que gastaram mais que um valor (HAVING)',
    'Mesma consulta do item 3, mantendo só os grupos cujo total gasto é maior que o valor informado (o ? do SQL recebe esse valor).',
    "SELECT al.nome, COUNT(ac.id_acesso) AS qtd_refeicoes,
       SUM(ac.valor_debitado) AS total_gasto
FROM aluno al
JOIN carteirinha ct ON ct.id_aluno = al.id_aluno
JOIN acesso ac      ON ac.id_carteirinha = ct.id_carteirinha
GROUP BY al.id_aluno, al.nome
HAVING SUM(ac.valor_debitado) > ?
ORDER BY total_gasto DESC",
    [$minimo], 'd', $formMinimo);

// 5) Subconsulta
relatorio($conn, 5, 'Alunos com gasto acima da média (subconsulta)',
    'Usa a consulta agrupada como subconsulta: compara o total de cada aluno com a média dos totais de quem já acessou.',
    "SELECT al.nome, SUM(ac.valor_debitado) AS total_gasto
FROM aluno al
JOIN carteirinha ct ON ct.id_aluno = al.id_aluno
JOIN acesso ac      ON ac.id_carteirinha = ct.id_carteirinha
GROUP BY al.id_aluno, al.nome
HAVING SUM(ac.valor_debitado) > (
    SELECT AVG(t.total)
    FROM (SELECT SUM(a2.valor_debitado) AS total
          FROM acesso a2
          JOIN carteirinha c2 ON c2.id_carteirinha = a2.id_carteirinha
          GROUP BY c2.id_aluno) AS t
)
ORDER BY total_gasto DESC");

// 6) Extra: saldo calculado pela view
relatorio($conn, 6, 'Saldo atual de cada conta (view vw_saldo)',
    'Saldo calculado: pagamentos confirmados menos os valores debitados nos acessos.',
    "SELECT al.nome, c.status AS status_conta, s.saldo
FROM vw_saldo s
JOIN conta c  ON c.id_conta = s.id_conta
JOIN aluno al ON al.id_aluno = s.id_aluno
ORDER BY al.nome");

require 'footer.php';
