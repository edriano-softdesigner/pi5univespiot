<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/api/conexao.php';

use Google\Client;
use Google\Service\Sheets;

$credencialJson = __DIR__ . '/chave.json';
$client = new Client();
$client->setApplicationName('Consumo IOTEC');
$client->setScopes([Sheets::SPREADSHEETS_READONLY]);
$client->setAuthConfig($credencialJson);

$service = new Sheets($client);
$spreadsheetId = '';

$spreadsheet = $service->spreadsheets->get($spreadsheetId);
$sheetName = $spreadsheet->getSheets()[0]->getProperties()->title;

$range = $sheetName . '!A:H';
$response = $service->spreadsheets_values->get($spreadsheetId, $range);
$values = $response->getValues();

if (empty($values) || count($values) <= 1) {
    die("Nenhum dado encontrado na planilha.");
}

array_shift($values);

// Últimos 15 registros para gráficos
$ultimos = array_slice($values, -15);

$labels = [];
$energia = [];
$temp = [];
$umidade = [];
$corrente1 = [];
$corrente2 = [];
$tensao1 = [];
$tensao2 = [];

foreach ($ultimos as $row) {
    $labels[]    = isset($row[0]) ? substr($row[0], 0, 19) : null;
    $corrente1[] = isset($row[1]) ? (float)$row[1] : null;
    $corrente2[] = isset($row[2]) ? (float)$row[2] : null;
    $tensao1[]   = isset($row[3]) ? (float)$row[3] : null;
    $tensao2[]   = isset($row[4]) ? (float)$row[4] : null;
    $energia[]   = isset($row[5]) ? (float)$row[5] : null;
    $temp[]      = isset($row[6]) ? (float)$row[6] : null;
    $umidade[]   = isset($row[7]) ? (float)$row[7] : null;
}

// --- Cálculo dos últimos 30 dias ---
$hoje = new DateTime();
$inicio30 = (clone $hoje)->modify('-30 days');

$consumoMensal = 0;
$mediaTemp = $mediaUmidade = $mediaCorrente1 = $mediaCorrente2 = $mediaTensao1 = $mediaTensao2 = 0;
$cont = 0;

foreach ($values as $row) {
    if (!empty($row[0])) {
        $dataRegistro = new DateTime(substr($row[0], 0, 19));
        if ($dataRegistro >= $inicio30) {
            $consumoMensal += (float)($row[5] ?? 0);
            $mediaTemp     += (float)($row[6] ?? 0);
            $mediaUmidade  += (float)($row[7] ?? 0);
            $mediaCorrente1+= (float)($row[1] ?? 0);
            $mediaCorrente2+= (float)($row[2] ?? 0);
            $mediaTensao1  += (float)($row[3] ?? 0);
            $mediaTensao2  += (float)($row[4] ?? 0);
            $cont++;
        }
    }
}

if ($cont > 0) {
    $mediaTemp     /= $cont;
    $mediaUmidade  /= $cont;
    $mediaCorrente1/= $cont;
    $mediaCorrente2/= $cont;
    $mediaTensao1  /= $cont;
    $mediaTensao2  /= $cont;
}

// --- Média móvel para energia ---
function mediaMovel($dados, $periodo = 5) {
    $resultado = [];
    for ($i = 0; $i < count($dados); $i++) {
        $slice = array_slice($dados, max(0, $i - $periodo + 1), $periodo);
        $resultado[] = array_sum($slice) / count($slice);
    }
    return $resultado;
}
$energiaTrend = mediaMovel($energia, 5);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Monitoramento de Consumo IoT</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: "Roboto", "Segoe UI", Arial, sans-serif; background: linear-gradient(135deg, #f0f4f8, #e9ecef); margin: 0; padding: 20px; color: #333; }
        h1 { text-align: center; color: #003366; margin-bottom: 5px; }
        h2 { text-align: center; color: #666; font-weight: normal; margin-top: 0; }
        .summary { display: flex; flex-wrap: wrap; justify-content: center; gap: 20px; margin: 30px auto; max-width: 1000px; }
        .summary-card { flex: 1 1 180px; background: #fff; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); padding: 15px; text-align: center; }
        .summary-card h4 { margin: 0 0 10px; color: #004080; }
        .summary-card p { font-size: 18px; font-weight: bold; margin: 0; }
        .dashboard { display: flex; flex-wrap: wrap; justify-content: center; gap: 25px; margin-top: 30px; }
        .chart-card { flex: 1 1 400px; max-width: 600px; background: #fff; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); padding: 20px; }
        .chart-card h3 { margin: 0 0 15px; font-size: 18px; color: #004080; text-align: center; }
        canvas { width: 100% !important; height: 350px !important; }
    </style>
</head>
<body>
  <header class="topbar text-center p-3 bg-light">
    <h1>⚡ Monitoramento de Consumo IoT</h1>
    <div class="theme-switch mt-2">
      <a href="index.php" class="btn btn-outline-secondary">⬅️ Voltar ao Início</a>
      <a href="painel_controle.php" class="btn btn-outline-success">⚙️ Painel de Controle</a>
      <a href="analise.php" class="btn btn-outline-info">🔎 Análise Detalhada</a>
    </div>
  </header>

  <main class="container my-4">
    <h2 class="text-center">Últimos 30 dias</h2>
    <!-- Aqui entram os cards de resumo e os gráficos -->

    <!-- Cards de resumo -->
    <div class="summary">
        <div class="summary-card"><h4>Consumo Mensal</h4><p><?php echo number_format($consumoMensal, 2, ',', '.'); ?> kWh</p></div>
        <div class="summary-card"><h4>Temperatura Média</h4><p><?php echo number_format($mediaTemp, 1, ',', '.'); ?> °C</p></div>
        <div class="summary-card"><h4>Umidade Média</h4><p><?php echo number_format($mediaUmidade, 1, ',', '.'); ?> %</p></div>
        <div class="summary-card"><h4>Corrente Média</h4><p>F1: <?php echo number_format($mediaCorrente1, 1, ',', '.'); ?> A<br>F2: <?php echo number_format($mediaCorrente2, 1, ',', '.'); ?> A</p></div>
        <div class="summary-card"><h4>Tensão Média</h4><p>F1: <?php echo number_format($mediaTensao1, 1, ',', '.'); ?> V<br>F2: <?php echo number_format($mediaTensao2, 1, ',', '.'); ?> V</p></div>
    </div>

    <!-- Gráficos -->
    <div class="dashboard">
        <div class="chart-card"><h3>Consumo de Energia</h3><canvas id="graficoEnergia"></canvas></div>
        <div class="chart-card"><h3>Temperatura e Umidade</h3><canvas id="graficoClima"></canvas></div>
        <div class="chart-card"><h3>Corrente e Tensão</h3><canvas id="graficoEletrico"></canvas></div>
    </div>

    <script>
        const labels = <?php echo json_encode($labels, JSON_UNESCAPED_UNICODE); ?>;
    const energia = <?php echo json_encode($energia, JSON_UNESCAPED_UNICODE); ?>;
    const energiaTrend = <?php echo json_encode($energiaTrend, JSON_UNESCAPED_UNICODE); ?>;
    const temp = <?php echo json_encode($temp, JSON_UNESCAPED_UNICODE); ?>;
    const umidade = <?php echo json_encode($umidade, JSON_UNESCAPED_UNICODE); ?>;
    const corrente1 = <?php echo json_encode($corrente1, JSON_UNESCAPED_UNICODE); ?>;
    const corrente2 = <?php echo json_encode($corrente2, JSON_UNESCAPED_UNICODE); ?>;
    const tensao1 = <?php echo json_encode($tensao1, JSON_UNESCAPED_UNICODE); ?>;
    const tensao2 = <?php echo json_encode($tensao2, JSON_UNESCAPED_UNICODE); ?>;

    function createChart(ctx, datasets, title) {
        return new Chart(ctx, {
            type: 'line',
            data: { labels, datasets },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'top' },
                    title: { display: true, text: title },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.formattedValue;
                            }
                        }
                    }
                },
                scales: {
                    x: { ticks: { maxRotation: 45, minRotation: 30 } },
                    y: { beginAtZero: false }
                }
            }
        });
    }

    // Gráfico de energia com linha de tendência
    createChart(document.getElementById('graficoEnergia'), [
        {
            label: 'Consumo Total (kWh)',
            data: energia,
            borderColor: '#007bff',
            backgroundColor: 'rgba(0,123,255,0.1)',
            tension: 0.4,
            fill: true,
            pointRadius: 0,
            pointHoverRadius: 6
        },
        {
            label: 'Tendência (Média Móvel)',
            data: energiaTrend,
            borderColor: '#ffcc00',
            borderDash: [5,5],
            tension: 0.4,
            fill: false,
            pointRadius: 0,
            pointHoverRadius: 0
        }
    ], 'Consumo de Energia (Últimos 15 Registros)');

    // Gráfico de temperatura e umidade
    createChart(document.getElementById('graficoClima'), [
        {
            label: 'Temperatura (°C)',
            data: temp,
            borderColor: '#dc3545',
            backgroundColor: 'rgba(220,53,69,0.1)',
            tension: 0.4,
            fill: true,
            pointRadius: 0,
            pointHoverRadius: 6
        },
        {
            label: 'Umidade (%)',
            data: umidade,
            borderColor: '#28a745',
            backgroundColor: 'rgba(40,167,69,0.1)',
            tension: 0.4,
            fill: true,
            pointRadius: 0,
            pointHoverRadius: 6
        }
    ], 'Temperatura e Umidade (Últimos 15 Registros)');

    // Gráfico de corrente e tensão
    createChart(document.getElementById('graficoEletrico'), [
        {
            label: 'Corrente Fase 1 (A)',
            data: corrente1,
            borderColor: '#ff9800',
            backgroundColor: 'rgba(255,152,0,0.1)',
            tension: 0.4,
            fill: true,
            pointRadius: 0,
            pointHoverRadius: 6
        },
        {
            label: 'Corrente Fase 2 (A)',
            data: corrente2,
            borderColor: '#ff5722',
            backgroundColor: 'rgba(255,87,34,0.1)',
            tension: 0.4,
            fill: true,
            pointRadius: 0,
            pointHoverRadius: 6
        },
        {
            label: 'Tensão Fase 1 (V)',
            data: tensao1,
            borderColor: '#6f42c1',
            backgroundColor: 'rgba(111,66,193,0.1)',
            tension: 0.4,
            fill: true,
            pointRadius: 0,
            pointHoverRadius: 6
        },
        {
            label: 'Tensão Fase 2 (V)',
            data: tensao2,
            borderColor: '#20c997',
            backgroundColor: 'rgba(32,201,151,0.1)',
            tension: 0.4,
            fill: true,
            pointRadius: 0,
            pointHoverRadius: 6
        }
    ], 'Corrente e Tensão (Últimos 15 Registros)');
    </script>
</body>
</html>
