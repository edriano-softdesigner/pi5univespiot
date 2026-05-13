<?php
header('Content-Type: application/json; charset=utf-8');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once __DIR__ . '/../conexao.php';
$mysqli = $conn;
$mysqli->set_charset('utf8mb4');

try {
    $result = $mysqli->query("SELECT id, umidade1, umidade2, umidade3, temperatura, bomba, irrigacao, data_registro 
                              FROM dados_sensores 
                              ORDER BY data_registro DESC 
                              LIMIT 50");

    $dados = [];
    while ($row = $result->fetch_assoc()) {
        $dados[] = $row;
    }

    echo json_encode([
        "success" => true,
        "dados" => $dados
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "erro" => $e->getMessage()
    ]);
}

$mysqli->close();
?>

