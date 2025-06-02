<?php
/*
Integrantes do grupo:
- Rodrigo Lucas – 10365071
- Vitor Machado – 10409358
- Vinícius Magno – 10401365

RESUMO DE FUNCIONALIDADES:
- Recebe parâmetro GET “url” apontando para site de blog (ex.: https://ecycle.com.br/).
- Faz download do conteúdo HTML remoto via file_get_contents (sem cURL).
- Remove eventuais instruções de X-Frame-Options / Content-Security-Policy que bloqueiem embed.
- Retorna apenas o body HTML para ser exibido dentro do <iframe> no chat.html.
*/

// 1) Verifica se veio o parâmetro “url”
if (!isset($_GET['url']) || trim($_GET['url']) === "") {
    http_response_code(400);
    echo "Parâmetro 'url' não informado.";
    exit;
}

$url = urldecode($_GET['url']);

// 2) Valida sintaticamente que começa com http:// ou https://
if (!preg_match('#^https?://#i', $url)) {
    http_response_code(400);
    echo "URL inválida.";
    exit;
}

// 3) Prepara um contexto para permitir buscas remotas (caso o servidor permita)
$options = [
    "http" => [
        "method" => "GET",
        // define um user-agent similar a um navegador
        "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\n",
        "timeout" => 20, // segundos
    ]
];
$context = stream_context_create($options);

// 4) Tenta baixar todo o conteúdo (inclusive cabeçalhos inline) do URL remoto
$raw = @file_get_contents($url, false, $context);
if ($raw === false) {
    http_response_code(502);
    echo "Erro ao acessar URL remota.";
    exit;
}

// 5) Se possível, tenta extrair o conteúdo do cabeçalho que o PHP possa ter armazenado
// A biblioteca file_get_contents não devolve cabeçalhos HTTP diretamente no corpo,
// mas podemos verificar $http_response_header se houver necessidade de status code.
$status_line = isset($http_response_header[0]) ? $http_response_header[0] : "";
if (preg_match('#\s(\d{3})\s#', $status_line, $matches)) {
    $http_code = intval($matches[1]);
    if ($http_code >= 400) {
        http_response_code($http_code);
        echo "Erro ao acessar o site remoto (HTTP $http_code).";
        exit;
    }
}

// 6) Vamos tentar separar cabeçalhos e corpo manualmente apenas se os cabeçalhos vierem embutidos.
// Na maioria das configurações, file_get_contents retorna só o HTML (sem headers). 
// Para manter compatibilidade, vamos assumir que '$raw' já seja apenas o corpo HTML.
// Caso você queira garantir a remoção de tags <meta http-equiv="Content-Security-Policy">, pode filtrar abaixo.

$body = $raw;

// 7) Remove eventuais meta-tags de CSP e X-Frame-Options embutidas no <head>
//    (Exemplo: <meta http-equiv="Content-Security-Policy" content="...">)
//    e também remove qualquer <meta http-equiv="X-Frame-Options" ...>.
$body = preg_replace(
    [
        '/<meta\s+http-equiv=["\']?Content-Security-Policy["\']?[^>]*>/i',
        '/<meta\s+http-equiv=["\']?X-Frame-Options["\']?[^>]*>/i'
    ],
    "",
    $body
);

// 8) Ajusta o Content-Type para “text/html” com o mesmo charset do remoto (se fornecido)
$content_type = "text/html; charset=UTF-8";
// Se houver header “Content-Type” no $http_response_header, tenta extrair
foreach ($http_response_header as $hdr) {
    if (stripos($hdr, "Content-Type:") === 0) {
        $content_type = trim(substr($hdr, strlen("Content-Type:")));
        break;
    }
}
header("Content-Type: $content_type");

// 9) Imprime apenas o corpo filtrado
echo $body;
