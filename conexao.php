<?php
// Conexão com o MySQL do XAMPP (usuário padrão: root, sem senha)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli('localhost', 'root', '', 'refeitorio');
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    die('Erro de conexão com o banco: ' . htmlspecialchars($e->getMessage()));
}