<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Carrega conexão
require_once __DIR__ . '/../conexao.php';

// Busca estado e modo atuais
$controle = ['estado' => '-', 'modo' => '-'];
if ($res = $conn->query("SELECT estado, modo FROM controle WHERE id = 1")) {
    $controle = $res->fetch_assoc();
    $res->free();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Painel de Controle da Irrigação</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #eef;
      padding: 30px;
      text-align: center;
    }
    h1 {
      margin-bottom: 20px;
    }
    .status {
      font-size: 1.2em;
      margin-bottom: 20px;
    }
    button {
      padding: 12px 20px;
      margin: 10px;
      font-size: 1em;
      cursor: pointer;
    }
    .back-button {
      margin-top: 40px;
      background-color: #ccc;
    }
  </style>
</head>
<body>
  <h1>🔧 Painel de Controle da Bomba Solenoide</h1>

  <div class="status">
    <p>Modo atual: <strong id="modo"><?php echo htmlspecialchars($controle['modo'], ENT_QUOTES, 'UTF-8'); ?></strong></p>
    <p>Estado atual: <strong id="estado"><?php echo htmlspecialchars($controle['estado'], ENT_QUOTES, 'UTF-8'); ?></strong></p>
  </div>

  <div>
    <button onclick="alterarControle('AUTO')">Ativar Modo Automático</button>
    <button onclick="alterarControle('ON')">Ligar Manualmente</button>
    <button onclick="alterarControle('OFF')">Desligar Manualmente</button>
  </div>

  <div>
    <button class="back-button" onclick="voltar()">⬅️ Voltar para Monitoramento</button>
  </div>

  <script>
    const API_BASE = '/api';

    async function alterarControle(acao) {
      try {
        const res = await fetch(`${API_BASE}/controle.php?acao=${acao}`);
        if (!res.ok) throw new Error(`Erro HTTP ${res.status}`);
        const data = await res.json();
        document.getElementById('estado').innerText = data.estado || '-';
        document.getElementById('modo').innerText   = data.modo   || '-';
      } catch (err) {
        alert('Erro ao alterar controle: ' + err.message);
      }
    }

    function voltar() {
      window.location.href = '/../index.php';
    }
  </script>
</body>
</html>
