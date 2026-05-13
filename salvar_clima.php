<?php
// public_html/api/salvar_clima.php
header('Content-Type: application/json; charset=utf-8');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once __DIR__ . '/../conexao.php';
$mysqli = $conn;
$mysqli->set_charset('utf8mb4');

// Lê o corpo da requisição
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    // fallback para form-urlencoded
    $data = $_POST;
}

// Campos obrigatórios para clima
$camposObrigatorios = ['temperatura','umidade_ar'];
foreach ($camposObrigatorios as $c) {
    if (!isset($data[$c])) {
        http_response_code(400);
        echo json_encode(['erro' => "Campo obrigatório ausente: $c"]);
        $mysqli->close();
        exit;
    }
}

// Sanitiza/converte valores
$temp = (float)$data['temperatura'];
$umidade_ar = (int)$data['umidade_ar'];

try {
    // Insere na tabela dados_clima
    $stmt = $mysqli->prepare(
        "INSERT INTO dados_clima (temperatura, umidade_ar)
         VALUES (?, ?)"
    );
    if ($stmt === false) {
        throw new Exception('Erro ao preparar statement: ' . $mysqli->error);
    }
    $stmt->bind_param("di", $temp, $umidade_ar);
    $stmt->execute();

    echo json_encode([
        'sucesso' => true,
        'id' => $stmt->insert_id,
        'temperatura' => $temp,
        'umidade_ar' => $umidade_ar
    ]);

    $stmt->close();
} catch (Exception $e) {
    http_response_code(500);
    error_log('salvar_clima.php error: ' . $e->getMessage());
    echo json_encode(['erro' => 'Falha ao salvar dados']);
}

$mysqli->close();
