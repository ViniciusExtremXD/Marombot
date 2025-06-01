<?php

/*
Integrantes do grupo:
- Rodrigo Lucas – 10365071
- Vitor Machado – 10409358
- Vinícius Magno – 10401365

RESUMO DE FUNCIONALIDADES:
- Script PHP similar a proxy.php, específico para requisições de blogs.
- Recebe parâmetro GET “url” apontando para site de blog (ex.: ecycle.com.br).
- Executa chamada cURL e devolve o HTML do blog para dentro de um iframe.
- Utilizado por blogs.html para exibir blogs dentro do contexto do Wall.ie.
*/

// 1) Verifica se veio o parâmetro “url”
if (!isset($_GET['url']) || empty($_GET['url'])) {
    http_response_code(400);
    echo "Parâmetro 'url' não informado.";
    exit;
}

$url = $_GET['url'];

// 2) Valida sintaticamente que começa com http:// ou https://
if (!preg_match('#^https?://#i', $url)) {
    http_response_code(400);
    echo "URL inválida.";
    exit;
}

// 3) Inicia cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
// Retornar corpo+headers
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// Incluir headers na saída
curl_setopt($ch, CURLOPT_HEADER, true);
// Seguir redirecionamentos automaticamente
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
// Definir um User-Agent comum de navegador
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
// Timeouts (opcional)
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$raw = curl_exec($ch);
if ($raw === false) {
    http_response_code(502);
    echo "Erro no cURL: " . curl_error($ch);
    curl_close($ch);
    exit;
}

// Pega informações de status
$info = curl_getinfo($ch);
$http_code = $info['http_code'];
$content_type = $info['content_type'];
$header_size = $info['header_size'];
curl_close($ch);

if ($http_code >= 400) {
    http_response_code($http_code);
    echo "Erro ao acessar o site remoto (HTTP $http_code).";
    exit;
}

// 4) Separa cabeçalhos vs corpo
$rawHeaders = substr($raw, 0, $header_size);
$body       = substr($raw, $header_size);

// 5) Filtra/remova cabeçalhos indesejados
//    – X-Frame-Options
//    – Content-Security-Policy (especialmente diretivas frame-ancestors)
//    – Possíveis cabeçalhos CSP “X-XSS-Protection” ou similares que impeçam embed
$filteredHeaders = [];

// Quebre os rawHeaders em linhas
$lines = preg_split('/\r\n|\n|\r/', $rawHeaders);
foreach ($lines as $line) {
    // Pula linhas vazias
    if (trim($line) === "") continue;

    // Se a linha for X-Frame-Options ou CSP, pule
    if (preg_match('/^X-Frame-Options:/i', $line)) continue;
    if (preg_match('/^Content-Security-Policy:/i', $line)) continue;
    if (preg_match('/^X-Content-Security-Policy:/i', $line)) continue;
    if (preg_match('/^X-WebKit-CSP:/i', $line)) continue;

    // Caso contrário, mantenha esse cabeçalho
    $filteredHeaders[] = $line;
}

// 6) Envie ao navegador apenas o Content-Type correto, ignorando o resto
if ($content_type) {
    header("Content-Type: $content_type");
} else {
    header("Content-Type: text/html; charset=UTF-8");
}

// (Opcional) Se você quiser repassar outros cabeçalhos seguros do site remoto,
// como “Cache-Control” ou “Expires”, poderia descomentar estas linhas:
// foreach ($filteredHeaders as $h) {
//     header($h);
// }

// 7) Imprime apenas o body HTML do site remoto
echo $body;
