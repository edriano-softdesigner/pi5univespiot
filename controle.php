<?php
// public_html/api/controle.php
header('Content-Type: application/json; charset=utf-8');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    require_once __DIR__ . '/../conexao.php';
    $mysqli = $conn;
    $mysqli->set_charset('utf8mb4');
} catch (Exception $e) {
    http_response_code(500);
    error_log('controle.php DB connect error: ' . $e->getMessage());
    echo json_encode(['erro' => 'Falha na conexão com o banco']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $res = $mysqli->query("SELECT id, estado, modo FROM controle WHERE id = 1 LIMIT 1");
        if ($res && ($row = $res->fetch_assoc())) {
            echo json_encode(['sucesso' => true, 'controle' => $row]);
        } else {
            // se não existir registro, cria um padrão
            $mysqli->query("INSERT INTO controle (id, estado, modo) VALUES (1, 'OFF', 'MANUAL') ON DUPLICATE KEY UPDATE id=id");
            echo json_encode(['sucesso' => true, 'controle' => ['id' => 1, 'estado' => 'OFF', 'modo' => 'MANUAL']]);
        }
        $mysqli->close();
        exit;
    }

    if ($method === 'POST') {
        // lê JSON ou form-urlencoded
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $data = $_POST;
        }

        // campos permitidos: estado (string), modo (string)
        $updates = [];
        $params = [];
        $types = '';

        if (isset($data['estado'])) {
            $updates[] = 'estado = ?';
            $params[] = (string) $data['estado'];
            $types .= 's';
        }
        if (isset($data['modo'])) {
            $updates[] = 'modo = ?';
            $params[] = (string) $data['modo'];
            $types .= 's';
        }

        if (count($updates) === 0) {
            http_response_code(400);
            echo json_encode(['erro' => 'Nenhum campo para atualizar (estado ou modo)']);
            $mysqli->close();
            exit;
        }

        // garante existência do registro controle id=1
        $mysqli->query("INSERT INTO controle (id, estado, modo) VALUES (1, 'OFF', 'MANUAL') ON DUPLICATE KEY UPDATE id=id");

        $sql = "UPDATE controle SET " . implode(', ', $updates) . " WHERE id = 1";
        $stmt = $mysqli->prepare($sql);
        if ($stmt === false) throw new Exception('Erro ao preparar stmt: ' . $mysqli->error);

        // bind dinâmica
        $bind_names[] = $types;
        for ($i = 0; $i < count($params); $i++) {
            $bind_names[] = &$params[$i];
        }
        call_user_func_array([$stmt, 'bind_param'], $bind_names);

        $stmt->execute();
        $stmt->close();

        // retorna estado atual
        $res2 = $mysqli->query("SELECT id, estado, modo FROM controle WHERE id = 1 LIMIT 1");
        $row = $res2->fetch_assoc();
        echo json_encode(['sucesso' => true, 'controle' => $row]);

        $mysqli->close();
        exit;
    }

    // método não permitido
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido']);
} catch (Exception $e) {
    http_response_code(500);
    error_log('controle.php error: ' . $e->getMessage());
    echo json_encode(['erro' => 'Erro interno no servidor']);
    $mysqli->close();
}

