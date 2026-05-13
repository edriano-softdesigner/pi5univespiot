<?php
require("phpMQTT.php"); // Certifique-se de que o arquivo phpMQTT.php está na mesma pasta

$server = "";   // IP do broker Mosquitto
$port = ;               // Porta padrão MQTT
$username = "painelweb";    // Usuário configurado no Mosquitto
$password = "";       // Senha configurada no Mosquitto
$client_id = "phpClientWeb"; // Identificador único do cliente

// Cria objeto MQTT
$mqtt = new Bluerhinos\phpMQTT($server, $port, $client_id);

if(!$mqtt->connect(true, NULL, $username, $password)) {
    exit("❌ Falha ao conectar ao broker MQTT.\n");
}

// Assina os tópicos de sensores
$topics['sensores/#'] = array("qos"=>0, "function"=>"procMsg");
$mqtt->subscribe($topics, 0);

// Publica um comando de teste para o ESP32
$mqtt->publish("controladores/bomba", "ON", 0);

// Loop para processar mensagens recebidas
while($mqtt->proc()) {}

$mqtt->close();

// Função chamada quando chega mensagem
function procMsg($topic, $msg){
    echo "📩 Mensagem recebida em [$topic]: $msg\n";
}
?>
