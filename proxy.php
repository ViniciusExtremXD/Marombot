<?php

/*
Integrantes do grupo:
- Rodrigo Lucas – 10365071
- Vitor Machado – 10409358
- Vinícius Magno – 10401365

RESUMO DE FUNCIONALIDADES:
- Script PHP que recebe um parâmetro GET “url” e faz proxy de requisição cURL para esse endereço.
- Definido para contornar restrições de CORS ao carregar conteúdo externo (ex.: blogs).
- Retorna o conteúdo bruto do endpoint remoto para o navegador do usuário.
*/

header("Content-Type: application/json");

// Lê o corpo JSON enviado pelo fetch() no script.js
$body = file_get_contents('php://input');
$data = json_decode($body, true);

if (!isset($data['mensagens'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Formato inválido']);
    exit;
}

// Extrai as mensagens (array de { author: "...", content: "..." })
$messages = $data['mensagens'];

// Substitua "SUA_API_KEY_GEMINI" pela sua chave de API real
$apiKey = 'SUA_API_KEY_GEMINI';

// Configura o endpoint da Gemini API
$url = 'https://gemini.googleapis.com/v1/models/gemini-1:generateMessage?key=' . urlencode($apiKey);

// Monta o payload para enviar à Gemini
$payload = [
    'prompt' => [
        'messages' => $messages
    ],
    'temperature' => 0.2,
    'candidateCount' => 1
];

// Inicia cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

// Executa a chamada
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    http_response_code($httpCode);
    echo $response;
    exit;
}

// Retorna a resposta da Gemini diretamente ao frontend
echo $response;
