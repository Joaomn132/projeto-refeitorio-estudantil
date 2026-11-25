<?php
include 'conexao.php';
$r = $conn->query('SELECT COUNT(*) AS total FROM aluno');
$linha = $r->fetch_assoc();
echo 'Conectou! Alunos cadastrados: ' . $linha['total'];