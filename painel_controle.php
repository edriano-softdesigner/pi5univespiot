<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/conexao.php';

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
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Temas -->
  <link id="theme-bootstrap" rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link id="theme-material" rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css" disabled>
  <link rel="stylesheet" href="css/custom-theme.css">
</head>
<body class="d-flex justify-content-center align-items-center min-vh-100 bg-light">
  <div class="container-control text-center">
    <!-- Alternância de temas -->
    <div class="theme-switch mb-3">
      <button class="btn btn-sm btn-primary" onclick="setTheme('bootstrap')">Bootstrap</button>
      <button class="btn btn-sm btn-secondary" onclick="setTheme('material')">Material</button>
      <button class="btn btn-sm btn-dark" onclick="toggleDarkMode()">🌙 Dark</button>
    </div>

    <h1 class="mb-4">🔧 Painel de Controle da Irrigação</h1>

    <!-- Status atual -->
    <div class="card p-3 mb-4">
      <h2 class="h6">Status Atual</h2>
      <p>Modo atual: <strong id="modo"><?=htmlspecialchars($controle['modo'], ENT_QUOTES, 'UTF-8');?></strong></p>
      <p>Estado atual: <strong id="estado"><?=htmlspecialchars($controle['estado'], ENT_QUOTES, 'UTF-8');?></strong></p>
    </div>

    <!-- Botões de controle -->
    <div class="card p-3 mb-4">
      <h2 class="h6">Controles</h2>
      <div class="d-grid gap-2">
        <button class="btn btn-success" onclick="alterarControle('AUTO')">Ativar Automático</button>
        <button class="btn btn-warning" onclick="alterarControle('ON')">Ligar Manual</button>
        <button class="btn btn-danger" onclick="alterarControle('OFF')">Desligar Manual</button>
      </div>
    </div>

    <!-- Voltar -->
    <a href="index.php" class="btn btn-outline-secondary">⬅️ Voltar</a>
  </div>

  <script>
    const API_BASE = '/api/';

    async function alterarControle(acao) {
      try {
        const res = await fetch(`${API_BASE}controle.php?acao=${acao}`);
        if (!res.ok) throw new Error(`Erro HTTP ${res.status}`);
        const data = await res.json();
        document.getElementById('estado').innerText = data.estado || '-';
        document.getElementById('modo').innerText   = data.modo   || '-';
      } catch (err) {
        alert('Erro ao alterar controle: ' + err.message);
      }
    }

    // Alternância de temas
    function setTheme(theme) {
      const bootstrap = document.getElementById('theme-bootstrap');
      const material  = document.getElementById('theme-material');
      if (theme === 'bootstrap') {
        bootstrap.removeAttribute('disabled');
        material.setAttribute('disabled', 'true');
      } else {
        material.removeAttribute('disabled');
        bootstrap.setAttribute('disabled', 'true');
      }
    }

    // Dark mode persistente
    function toggleDarkMode(){
      document.body.classList.toggle("dark-mode");
      if(document.body.classList.contains("dark-mode")){
        localStorage.setItem("darkMode","true");
      } else {
        localStorage.removeItem("darkMode");
      }
    }
    window.onload = () => {
      if(localStorage.getItem("darkMode")==="true"){
        document.body.classList.add("dark-mode");
      }
    };
  </script>
</body>
</html>

