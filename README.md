# Wall.ie

## Integrantes
- Rodrigo Lucas – 10365071  
- Vitor Machado – 10409358  
- Vinícius Magno – 10401365  

## Dependências e Como Executar
1. **PHP 7.4+** instalado (para rodar servidor embutido e evitar bloqueios de CORS).  
2. **Navegador moderno** com suporte a `fetch` e Geolocation API (Chrome, Firefox, Edge, Safari).  
3. **Chave da Google Maps Embed API** (substituir em `script.js`).  
4. **Chave da Google Gemini API** já configurada em `script.js` (const API_KEY).  

**Passos para executar localmente:**
- Abra um terminal dentro da pasta `/Wall.ie/`.  
- Rode:
php -S localhost:8000

- No navegador, acesse:
http://localhost:8000/index.html

- Faça login com credenciais fictícias (usuário: `teste_user`, senha: `senha123`).  

## Estrutura de Pastas

/Wall.ie/
├── login.html       ← Página de login
├── chat.html        ← Interface principal do chat Wallie
├── blogs.html       ← Lista de portais/blogs sobre reciclagem
├── script.js        ← Lógica de chat, integração com Gemini e embeds
└── README.md        ← Documentação (este arquivo)

## Descrição Geral
Wall.ie é um assistente web focado em reciclagem de lixo eletrônico. Possui chat integrado a um modelo de linguagem (Gemini) e recursos embutidos como:
- Mapa de pontos de coleta (ecopontos)  
- Vídeos educativos no YouTube  
- Lista de blogs sobre reciclagem  
- Podcasts no Spotify  


## Funcionalidades Principais

### 1. Página de Login (login.html)
- Campos “Usuário” e “Senha” fictícios.  
- Redireciona para `chat.html` via JavaScript.  

### 2. Chat com API Gemini (chat.html + script.js)
- **Painel esquerdo**:  
  - “Principais perguntas” (clicáveis) que repetem a pergunta no chat.  
  - Botões para Ecoponto, Vídeos, Blogs e Podcasts.

- **Painel direito**:  
  - Histórico de mensagens.  
  - Formulário para nova pergunta. 

- **Integração com Gemini v1beta**:  
  - Monta JSON com histórico de conversa + instrução implícita:  
    > “Você é um assistente que responde apenas sobre lixo eletrônico de forma curta e direta. Se a pergunta não for sobre esse tema, responda:  
    > ‘Desculpe, mas ajudo apenas com lixo eletrônico, quer tentar novamente?’.”  
 
  - Envia via `fetch` (POST) para:  

    https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key=<API_KEY>
 
  - Exibe “Wallie está digitando...” enquanto aguarda resposta.  
  - Mantém histórico em memória para contexto.  

### 3. Embeds de Conteúdo (iframe)
- **Ecoponto**:  
  - Carrega Google Maps embed com filtro `q=ecoponto` (sem geolocalização).  
  - Requer substituir `MAPS_API_KEY` em `script.js`.  
- **Vídeos**:  
  - Embed de playlist do YouTube em iframe.  
- **Blogs**:  
  - Carrega `blogs.html` dentro de iframe.  
- **Podcasts**:  
  - Embed de episódio do Spotify.  
- Botão “Voltar para o Chat” fecha iframe e retorna à interface.  

### 4. Blogs sobre Reciclagem (blogs.html)
- Lista de portais/blogs parceiros (ex.: eCycle, CicloVivo).  
- Cada item tem:  
  - Nome do site  
  - Descrição breve  
  - Botão “Abrir” que carrega o site dentro de um iframe no próprio `blogs.html`.  
- Botão “Voltar para o Chat” retorna a `chat.html`.  



## Credenciais de Teste
- Usuário: teste_user 
- Senha: senha123  
*(Não há autenticação real; apenas redireciona para `chat.html`.)*



## Histórico de Versões

### 0.1 (Beta)
- Implementação de login estático (redireciona para chat).  
- Chat com mensagem de boas-vindas.  
- Integração com Gemini v1beta (prompt de sistema implícito).  
- Indicador “Wallie está digitando…” ao enviar pergunta.  
- **Ecoponto**: busca “ecoponto” no Google Maps embed.  
- **Vídeos**: embed de playlist do YouTube.  
- **Blogs**: `blogs.html` para listar portais em iframe.  
- **Podcasts**: embed de episódio do Spotify.  
- Botões “Voltar para o Chat” para fechar iframe.  
- Layout responsivo e estilos ajustados.  
