<?php
// Configurações de acesso ao banco de dados
$host     = 'localhost';
$usuario  = '';
$senha    = '';
$banco    = '';

// Cria a conexão
$conn = new mysqli($host, $usuario, $senha, $banco);

// Verifica se houve erro na conexão
if ($conn->connect_error) {
    die('Falha na conexão: ' . $conn->connect_error);
}

// Define charset para evitar problemas de acentuação
$conn->set_charset('utf8mb4');
?>
