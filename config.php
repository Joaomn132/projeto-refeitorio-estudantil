<?php
// Configuração geral do sistema: sessão, fuso, conexão, funções e
// descrição das 6 tabelas (o CRUD genérico se monta a partir daqui).

date_default_timezone_set('America/Sao_Paulo');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/conexao.php';

function h($s)
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function flash($tipo, $texto)
{
    $_SESSION['flash'] = [$tipo, $texto];
}

function mensagem_erro(mysqli_sql_exception $e)
{
    $c = (int)$e->getCode();
    $m = $e->getMessage();
    if ($c === 1644) {
        return 'Regra de negócio (gatilho): ' . $m;
    }
    if ($c === 1451) {
        return 'Não é possível excluir: existem registros de outras tabelas ligados a este.';
    }
    if ($c === 1452) {
        return 'Registro relacionado não encontrado (chave estrangeira inválida).';
    }
    if ($c === 1062) {
        return 'Valor duplicado (campo único ou regra de unicidade violada): ' . $m;
    }
    if ($c === 3819 || $c === 4025) {
        return 'Valor recusado por uma regra de validação (CHECK): ' . $m;
    }
    return 'Erro no banco de dados (' . $c . '): ' . $m;
}

function carregar_opcoes($conn, $campo)
{
    if ($campo['tipo'] === 'select') {
        return array_combine($campo['opcoes'], $campo['opcoes']);
    }
    $opcoes = [];
    $res = $conn->query($campo['sql']);
    while ($r = $res->fetch_assoc()) {
        $opcoes[$r['id']] = $r['rotulo'];
    }
    return $opcoes;
}

function formatar($campo, $valor, $opcoes)
{
    if ($valor === null) {
        return '—';
    }
    switch ($campo['tipo']) {
        case 'fk':
            return $opcoes[$valor] ?? $valor;
        case 'date':
            return date('d/m/Y', strtotime($valor));
        case 'datetime':
            return date('d/m/Y H:i', strtotime($valor));
        case 'number':
            return !empty($campo['moeda'])
                ? 'R$ ' . number_format((float)$valor, 2, ',', '.')
                : $valor;
        default:
            return $valor;
    }
}

$TABELAS = [
    'aluno' => [
        'titulo' => 'Alunos',
        'singular' => 'aluno',
        'pk' => 'id_aluno',
        'campos' => [
            'matricula' => ['rotulo' => 'Matrícula', 'tipo' => 'text', 'max' => 20],
            'nome'      => ['rotulo' => 'Nome',      'tipo' => 'text', 'max' => 100],
            'curso'     => ['rotulo' => 'Curso',     'tipo' => 'text', 'max' => 60],
            'email'     => ['rotulo' => 'E-mail',    'tipo' => 'email', 'max' => 100],
        ],
    ],
    'carteirinha' => [
        'titulo' => 'Carteirinhas',
        'singular' => 'carteirinha',
        'pk' => 'id_carteirinha',
        'campos' => [
            'id_aluno' => [
                'rotulo' => 'Aluno', 'tipo' => 'fk',
                'sql' => "SELECT id_aluno AS id, CONCAT(matricula, ' - ', nome) AS rotulo FROM aluno ORDER BY nome",
            ],
            'codigo'   => ['rotulo' => 'Código',   'tipo' => 'text', 'max' => 30],
            'validade' => ['rotulo' => 'Validade', 'tipo' => 'date'],
            'status'   => ['rotulo' => 'Status',   'tipo' => 'select', 'opcoes' => ['ATIVA', 'INATIVA', 'BLOQUEADA']],
        ],
    ],
    'conta' => [
        'titulo' => 'Contas',
        'singular' => 'conta',
        'pk' => 'id_conta',
        'campos' => [
            'id_aluno' => [
                'rotulo' => 'Aluno', 'tipo' => 'fk',
                'sql' => "SELECT id_aluno AS id, CONCAT(matricula, ' - ', nome) AS rotulo FROM aluno ORDER BY nome",
            ],
            'status' => ['rotulo' => 'Status', 'tipo' => 'select', 'opcoes' => ['ATIVA', 'BLOQUEADA']],
        ],
    ],
    'refeicao' => [
        'titulo' => 'Refeições',
        'singular' => 'refeição',
        'pk' => 'id_refeicao',
        'campos' => [
            'data'     => ['rotulo' => 'Data',     'tipo' => 'date'],
            'tipo'     => ['rotulo' => 'Tipo',     'tipo' => 'select', 'opcoes' => ['ALMOCO', 'JANTAR']],
            'cardapio' => ['rotulo' => 'Cardápio', 'tipo' => 'text', 'max' => 200],
            'preco'    => ['rotulo' => 'Preço',    'tipo' => 'number', 'moeda' => true],
        ],
    ],
    'pagamento' => [
        'titulo' => 'Pagamentos',
        'singular' => 'pagamento',
        'pk' => 'id_pagamento',
        'campos' => [
            'id_conta' => [
                'rotulo' => 'Conta', 'tipo' => 'fk',
                'sql' => "SELECT c.id_conta AS id, CONCAT('Conta ', c.id_conta, ' - ', a.nome) AS rotulo FROM conta c JOIN aluno a ON a.id_aluno = c.id_aluno ORDER BY a.nome",
            ],
            'valor'           => ['rotulo' => 'Valor', 'tipo' => 'number', 'moeda' => true],
            'data_hora'       => ['rotulo' => 'Data e hora', 'tipo' => 'datetime'],
            'forma_pagamento' => ['rotulo' => 'Forma de pagamento', 'tipo' => 'select', 'opcoes' => ['PIX', 'CARTAO', 'DINHEIRO']],
            'status'          => ['rotulo' => 'Status', 'tipo' => 'select', 'opcoes' => ['PENDENTE', 'CONFIRMADO', 'CANCELADO']],
        ],
    ],
    'acesso' => [
        'titulo' => 'Acessos',
        'singular' => 'acesso',
        'pk' => 'id_acesso',
        'campos' => [
            'id_carteirinha' => [
                'rotulo' => 'Carteirinha', 'tipo' => 'fk',
                'sql' => "SELECT ct.id_carteirinha AS id, CONCAT(ct.codigo, ' - ', a.nome) AS rotulo FROM carteirinha ct JOIN aluno a ON a.id_aluno = ct.id_aluno ORDER BY a.nome, ct.codigo",
            ],
            'id_refeicao' => [
                'rotulo' => 'Refeição', 'tipo' => 'fk',
                'sql' => "SELECT id_refeicao AS id, CONCAT(DATE_FORMAT(`data`, '%d/%m/%Y'), ' ', tipo, ' - R$ ', FORMAT(preco, 2)) AS rotulo FROM refeicao ORDER BY `data`, tipo",
            ],
            'data_hora' => ['rotulo' => 'Data e hora', 'tipo' => 'datetime'],
            'valor_debitado' => [
                'rotulo' => 'Valor debitado', 'tipo' => 'number', 'moeda' => true,
                'obrigatorio' => false, 'padrao' => '1.00',
                'dica' => 'Pode ficar em branco: o gatilho grava o preço da refeição.',
            ],
        ],
    ],
];
