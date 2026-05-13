<?php
// public_html/api/salvar_dados.php
header('Content-Type: application/json; charset=utf-8');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once __DIR__ . '/../conexao.php';
$mysqli = $conn;
$mysqli->set_charset('utf8mb4');


$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    // fallback para form-urlencoded
    $data = $_POST;
}

// Campos esperados (temperatura agora opcional)
$camposObrigatorios = ['umidade1','umidade2','umidade3','bomba','irrigacao'];
foreach ($camposObrigatorios as $c) {
    if (!isset($data[$c])) {
        http_response_code(400);
        echo json_encode(['erro' => "Campo obrigatório ausente: $c"]);
        $mysqli->close();
        exit;
    }
}

// Sanitiza/converte valores
$u1 = (int)$data['umidade1'];
$u2 = (int)$data['umidade2'];
$u3 = (int)$data['umidade3'];
$temp = isset($data['temperatura']) ? (float)$data['temperatura'] : null; // opcional
$bomba = (int)$data['bomba'];
$irrigacao = (int)$data['irrigacao'];

try {
    // Insere na tabela dados_sensores
    $stmt = $mysqli->prepare(
        "INSERT INTO dados_sensores (umidade1, umidade2, umidade3, temperatura, bomba, irrigacao)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    if ($stmt === false) {
        throw new Exception('Erro ao preparar statement: ' . $mysqli->error);
    }
    $stmt->bind_param("iiidii", $u1, $u2, $u3, $temp, $bomba, $irrigacao);
    $stmt->execute();

    echo json_encode([
        'sucesso' => true,
        'id' => $stmt->insert_id,
        'umidade1' => $u1,
        'umidade2' => $u2,
        'umidade3' => $u3,
        'temperatura' => $temp,
        'bomba' => $bomba,
        'irrigacao' => $irrigacao
    ]);

    $stmt->close();
} catch (Exception $e) {
    http_response_code(500);
    error_log('salvar_dados.php error: ' . $e->getMessage());
    echo json_encode(['erro' => 'Falha ao salvar dados']);
}

$mysqli->close();
