<?php
// Encerra a sessão do administrador.
require 'config.php';
unset($_SESSION['admin']);
flash('ok', 'Você saiu da área administrativa.');
header('Location: index.php');
exit;
