<?php
/*
Integrantes do grupo:
- Rodrigo Lucas – 10365071
- Vitor Machado – 10409358
- Vinícius Magno – 10401365

RESUMO DE FUNCIONALIDADES:
- Recebe parâmetro GET "url" apontando para site de blog (ex.: https://ecycle.com.br/).
- Faz download do conteúdo HTML remoto via file_get_contents() com stream context.
- Remove instruções que bloqueiem embed e ajusta o conteúdo para exibição em iframe.
- Retorna o HTML processado para ser exibido dentro do <iframe> no chat.html.
*/

// Configurações
ini_set('max_execution_time', 30);
ini_set('memory_limit', '256M');

// Headers para evitar cache e melhorar compatibilidade
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// 1) Verifica se veio o parâmetro "url"
if (!isset($_GET['url']) || trim($_GET['url']) === "") {
    http_response_code(400);
    echo createErrorPage("Parâmetro 'url' não informado.");
    exit;
}

$url = urldecode($_GET['url']);
$original_url = $url;

// 2) Valida sintaticamente que começa com http:// ou https://
if (!preg_match('#^https?://#i', $url)) {
    http_response_code(400);
    echo createErrorPage("URL inválida: $url");
    exit;
}

// 3) Lista de URLs permitidas (segurança)
$allowed_domains = [
    'ecycle.com.br',
    'ciclovivo.com.br',
    'portaldomeioambiente.org.br',
    'ecodebate.com.br',
    'greenme.com.br',
    'pensamentoverde.com.br'
];

$parsed_url = parse_url($url);
$domain = isset($parsed_url['host']) ? strtolower($parsed_url['host']) : '';
$domain = preg_replace('/^www\./', '', $domain);

$is_allowed = false;
foreach ($allowed_domains as $allowed) {
    if ($domain === $allowed || str_ends_with($domain, '.' . $allowed)) {
        $is_allowed = true;
        break;
    }
}

if (!$is_allowed) {
    http_response_code(403);
    echo createErrorPage("Domínio não permitido: $domain");
    exit;
}

// 4) Função para fazer requisição HTTP usando file_get_contents
function fetchContent($url) {
    $max_redirects = 5;
    $redirect_count = 0;
    
    while ($redirect_count < $max_redirects) {
        // Cria o context para file_get_contents
        $context_options = [
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language: pt-BR,pt;q=0.9,en;q=0.8',
                    'Accept-Encoding: gzip, deflate',
                    'Connection: close',
                    'Upgrade-Insecure-Requests: 1'
                ],
                'timeout' => 20,
                'follow_location' => 0, // Não seguir redirects automaticamente
                'ignore_errors' => true, // Para capturar códigos de erro
                'max_redirects' => 0
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];
        
        $context = stream_context_create($context_options);
        
        // Faz a requisição
        $content = @file_get_contents($url, false, $context);
        
        if ($content === false) {
            $error = error_get_last();
            throw new Exception("Erro ao acessar URL: " . ($error['message'] ?? 'Erro desconhecido'));
        }
        
        // Analisa os headers de resposta
        if (!isset($http_response_header) || empty($http_response_header)) {
            throw new Exception("Não foi possível obter headers de resposta");
        }
        
        // Extrai código HTTP
        $status_line = $http_response_header[0];
        if (!preg_match('/HTTP\/\d+\.\d+\s+(\d{3})/', $status_line, $matches)) {
            throw new Exception("Não foi possível determinar código HTTP");
        }
        
        $http_code = intval($matches[1]);
        
        // Extrai Content-Type
        $content_type = 'text/html';
        foreach ($http_response_header as $header) {
            if (stripos($header, 'Content-Type:') === 0) {
                $content_type = trim(substr($header, 13));
                break;
            }
        }
        
        // Verifica se é redirect
        if ($http_code >= 300 && $http_code < 400) {
            $location = null;
            foreach ($http_response_header as $header) {
                if (stripos($header, 'Location:') === 0) {
                    $location = trim(substr($header, 9));
                    break;
                }
            }
            
            if ($location) {
                // Se for URL relativa, converte para absoluta
                if (!preg_match('#^https?://#i', $location)) {
                    $parsed = parse_url($url);
                    $base = $parsed['scheme'] . '://' . $parsed['host'];
                    if (isset($parsed['port'])) {
                        $base .= ':' . $parsed['port'];
                    }
                    
                    if (substr($location, 0, 1) === '/') {
                        $location = $base . $location;
                    } else {
                        $path = isset($parsed['path']) ? dirname($parsed['path']) : '';
                        $location = $base . $path . '/' . $location;
                    }
                }
                
                $url = $location;
                $redirect_count++;
                continue;
            }
        }
        
        // Se chegou aqui, não é redirect
        if ($http_code >= 400) {
            throw new Exception("Erro HTTP $http_code ao acessar: $url");
        }
        
        // Verifica se é HTML
        if (!preg_match('/text\/html/i', $content_type)) {
            // Aceita se não tiver content-type definido
            if (trim($content_type) !== '' && !preg_match('/text\/plain/i', $content_type)) {
                throw new Exception("Conteúdo não é HTML. Content-Type: $content_type");
            }
        }
        
        // Decodifica gzip se necessário
        if (function_exists('gzdecode')) {
            // Verifica se o conteúdo está comprimido
            if (substr($content, 0, 2) === "\x1f\x8b") {
                $decoded = @gzdecode($content);
                if ($decoded !== false) {
                    $content = $decoded;
                }
            }
        }
        
        return [
            'body' => $content,
            'headers' => $http_response_header,
            'http_code' => $http_code,
            'content_type' => $content_type,
            'final_url' => $url
        ];
    }
    
    throw new Exception("Muitos redirects");
}

// 5) Faz a requisição
try {
    $result = fetchContent($url);
    $body = $result['body'];
    $content_type = $result['content_type'];
    $final_url = $result['final_url'];
} catch (Exception $e) {
    http_response_code(502);
    echo createErrorPage("Erro ao acessar o site: " . $e->getMessage());
    exit;
}

// 6) Processa o HTML
$body = processHTML($body, $final_url);

// 7) Define content-type
if (preg_match('/charset=([^;]+)/i', $content_type, $matches)) {
    $charset = trim($matches[1]);
} else {
    $charset = 'UTF-8';
}

header("Content-Type: text/html; charset=$charset");

// 8) Retorna o conteúdo processado
echo $body;

// ============= FUNÇÕES AUXILIARES =============

function processHTML($html, $base_url) {
    // Remove tags que impedem embedding
    $html = preg_replace([
        '/<meta\s+http-equiv=["\']?Content-Security-Policy["\']?[^>]*>/i',
        '/<meta\s+http-equiv=["\']?X-Frame-Options["\']?[^>]*>/i',
        '/<meta\s+name=["\']?referrer["\']?[^>]*>/i'
    ], '', $html);
    
    // Remove scripts que podem interferir
    $html = preg_replace([
        '/<script[^>]*>.*?<\/script>/is',
        '/<noscript[^>]*>.*?<\/noscript>/is'
    ], '', $html);
    
    // Converte URLs relativas para absolutas
    $parsed_base = parse_url($base_url);
    if (!$parsed_base) {
        return $html; // Se não conseguir fazer parse da URL, retorna como está
    }
    
    $base_host = $parsed_base['scheme'] . '://' . $parsed_base['host'];
    if (isset($parsed_base['port'])) {
        $base_host .= ':' . $parsed_base['port'];
    }
    
    // Corrige links e recursos relativos
    $html = preg_replace_callback(
        '/(href|src|action)=["\']([^"\']+)["\']/i',
        function($matches) use ($base_host, $base_url) {
            $attr = $matches[1];
            $url = $matches[2];
            
            // Se já é absoluta, mantém
            if (preg_match('#^https?://#i', $url) || preg_match('#^//#', $url)) {
                return $matches[0];
            }
            
            // Se começa com #, é âncora na mesma página
            if (substr($url, 0, 1) === '#') {
                return $matches[0];
            }
            
            // Se começa com /, é relativa ao domínio
            if (substr($url, 0, 1) === '/') {
                return $attr . '="' . $base_host . $url . '"';
            }
            
            // Senão, é relativa ao diretório atual
            $current_dir = dirname($base_url);
            if ($current_dir === $base_url) {
                $current_dir = $base_host;
            }
            return $attr . '="' . $current_dir . '/' . $url . '"';
        },
        $html
    );
    
    // Adiciona estilos para melhor visualização no iframe
    $iframe_styles = '
    <style>
        body { 
            margin: 0 !important; 
            padding: 10px !important; 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
            line-height: 1.5 !important;
            overflow-x: hidden !important;
        }
        
        * {
            box-sizing: border-box !important;
        }
        
        /* Remove elementos que podem quebrar o layout */
        iframe, embed, object { 
            max-width: 100% !important; 
            width: 100% !important;
        }
        img { 
            max-width: 100% !important; 
            height: auto !important; 
        }
        
        /* Melhora containers */
        .container, .content, .main {
            max-width: 100% !important;
            width: 100% !important;
        }
        
        /* Remove elementos fixos que podem atrapalhar */
        .fixed, .sticky, 
        [style*="position: fixed"], 
        [style*="position: sticky"],
        [style*="position:fixed"],
        [style*="position:sticky"] {
            position: relative !important;
        }
        
        /* Esconde alguns elementos comuns que podem ser problemáticos */
        .popup, .modal, .overlay, .advertisement, .ad-banner,
        [class*="popup"], [id*="popup"], 
        [class*="modal"], [id*="modal"],
        [class*="ad-"], [id*="ad-"],
        .social-share, .share-buttons,
        .newsletter-signup, .subscription-box {
            display: none !important;
        }
        
        /* Melhora a legibilidade */
        p, div, article, section {
            max-width: 100% !important;
            word-wrap: break-word !important;
        }
        
        /* Remove scrollbars horizontais */
        html, body {
            overflow-x: hidden !important;
        }
    </style>
    ';
    
    // Insere os estilos
    if (preg_match('/<\/head>/i', $html)) {
        $html = preg_replace('/<\/head>/i', $iframe_styles . '</head>', $html);
    } elseif (preg_match('/<body[^>]*>/i', $html)) {
        $html = preg_replace('/<body[^>]*>/i', '$0' . $iframe_styles, $html);
    } else {
        $html = $iframe_styles . $html;
    }
    
    return $html;
}

function createErrorPage($message) {
    return '
    <!DOCTYPE html>
    <html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Erro - Wall.ie</title>
        <style>
            body {
                font-family: "Poppins", sans-serif;
                margin: 0;
                padding: 20px;
                background-color: #f0f0f0;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                box-sizing: border-box;
            }
            .error-container {
                background: white;
                padding: 30px;
                border-radius: 10px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                text-align: center;
                max-width: 500px;
                width: 100%;
            }
            .error-icon {
                font-size: 48px;
                color: #e74c3c;
                margin-bottom: 20px;
            }
            .error-title {
                color: #127369;
                font-size: 24px;
                margin-bottom: 15px;
            }
            .error-message {
                color: #666;
                font-size: 16px;
                margin-bottom: 20px;
                line-height: 1.5;
            }
            .error-actions {
                margin-top: 25px;
            }
            .btn {
                background-color: #127369;
                color: white;
                border: none;
                padding: 10px 20px;
                border-radius: 5px;
                cursor: pointer;
                font-size: 14px;
                margin: 5px;
                text-decoration: none;
                display: inline-block;
            }
            .btn:hover {
                background-color: #0f5f58;
            }
            .btn-secondary {
                background-color: #6c757d;
            }
            .btn-secondary:hover {
                background-color: #545b62;
            }
            .suggestion {
                margin-top: 20px;
                padding: 15px;
                background-color: #e8f5e8;
                border-radius: 5px;
                font-size: 14px;
                color: #127369;
            }
        </style>
    </head>
    <body>
        <div class="error-container">
            <div class="error-icon">⚠️</div>
            <h2 class="error-title">Oops! Algo deu errado</h2>
            <p class="error-message">' . htmlspecialchars($message) . '</p>
            
            <div class="suggestion">
                💡 <strong>Dica:</strong> Tente usar o botão "Abrir em Nova Aba" para acessar o site diretamente.
            </div>
            
            <div class="error-actions">
                <button class="btn" onclick="goBack()">Voltar aos Blogs</button>
                <button class="btn btn-secondary" onclick="closeIframe()">Fechar</button>
            </div>
        </div>
        
        <script>
            function goBack() {
                if (window.parent && window.parent !== window) {
                    try {
                        // Volta para a lista de blogs
                        if (parent.document.getElementById("embeddedFrame")) {
                            parent.document.getElementById("embeddedFrame").src = "blogs.html";
                        }
                    } catch(e) {
                        history.back();
                    }
                } else {
                    history.back();
                }
            }
            
            function closeIframe() {
                if (window.parent && window.parent !== window) {
                    try {
                        if (parent.closeIframe) {
                            parent.closeIframe();
                        } else if (parent.document.getElementById("chatWindow")) {
                            parent.document.getElementById("chatWindow").style.display = "block";
                            parent.document.getElementById("chatForm").style.display = "block";
                            parent.document.getElementById("iframeContainer").style.display = "none";
                        }
                    } catch(e) {
                        window.close();
                    }
                } else {
                    window.close();
                }
            }
            
            // Auto-volta após 8 segundos
            setTimeout(goBack, 8000);
        </script>
    </body>
    </html>';
}
?>