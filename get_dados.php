<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../conexao.php';

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
$date  = $_GET['date'] ?? '';

try {
    // 1) Busca leituras (temperatura e umidade do ar)
    $sql1 = "SELECT id, temperatura, umidade_ar, created_at 
             FROM leituras";
    $params = [];
    if ($date) {
        $sql1 .= " WHERE DATE(created_at) = ?";
        $params[] = $date;
    }
    $sql1 .= " ORDER BY created_at DESC LIMIT ?";
    $params[] = $limit;

    $stmt1 = $conn->prepare($sql1);
    if ($date) {
        $stmt1->bind_param("si", $params[0], $params[1]);
    } else {
        $stmt1->bind_param("i", $params[0]);
    }
    $stmt1->execute();
    $res1 = $stmt1->get_result();

    $leituras = [];
    while ($row = $res1->fetch_assoc()) {
        $leituras[$row['id']] = [
            "id" => $row['id'],
            "temperatura" => $row['temperatura'],
            "umidade_ar"  => $row['umidade_ar'],
            "created_at"  => $row['created_at'],
            "umidade_solo" => null,
            "status" => null
        ];
    }

    // 2) Busca umidade do solo na tabela dados_umidade
    $sql2 = "SELECT id, umidade, status, data_registro 
             FROM dados_umidade";
    if ($date) {
        $sql2 .= " WHERE DATE(data_registro) = ?";
    }
    $sql2 .= " ORDER BY data_registro DESC LIMIT ?";
    $stmt2 = $conn->prepare($sql2);
    if ($date) {
        $stmt2->bind_param("si", $date, $limit);
    } else {
        $stmt2->bind_param("i", $limit);
    }
    $stmt2->execute();
    $res2 = $stmt2->get_result();

    while ($row = $res2->fetch_assoc()) {
        if (isset($leituras[$row['id']])) {
            $leituras[$row['id']]['umidade_solo'] = $row['umidade'];
            $leituras[$row['id']]['status'] = $row['status'];
            $leituras[$row['id']]['created_at'] = $row['data_registro'];
        } else {
            $leituras[$row['id']] = [
                "id" => $row['id'],
                "temperatura" => null,
                "umidade_ar"  => null,
                "created_at"  => $row['data_registro'],
                "umidade_solo" => $row['umidade'],
                "status" => $row['status']
            ];
        }
    }

    // Ordena por data
    usort($leituras, function($a, $b) {
        return strtotime($a['created_at']) <=> strtotime($b['created_at']);
    });

    // 3) Busca controle
    $controle = ['estado' => 'OFF', 'modo' => 'MANUAL'];
    $res3 = $conn->query("SELECT estado, modo FROM controle WHERE id = 1 LIMIT 1");
    if ($res3 && ($row3 = $res3->fetch_assoc())) {
        $controle = $row3;
    }

    // 4) Se modo AUTO, recalcula estado com base na última leitura de dados_umidade
    if ($controle['modo'] === 'AUTO') {
        $res4 = $conn->query("SELECT umidade, status FROM dados_umidade ORDER BY data_registro DESC LIMIT 1");
        if ($res4 && ($row4 = $res4->fetch_assoc())) {
            $umidade = (float)$row4['umidade'];
            $status  = $row4['status'];

            if ($status === 'Solo seco' || $umidade < 30) {
                $controle['estado'] = 'ON';
            } elseif ($status === 'Solo encharcado' || $umidade > 85) {
                $controle['estado'] = 'OFF';
            }
        }
    }

    echo json_encode([
        "sucesso" => true,
        "leituras" => $leituras,
        "controle" => $controle
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "sucesso" => false,
        "erro" => $e->getMessage()
    ]);
}
