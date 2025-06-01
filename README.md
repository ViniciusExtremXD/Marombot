# Wall.ie

**Integrantes do grupo:**
- Rodrigo Lucas – 10365071
- Vitor Machado – 10409358
- Vinícius Magno – 10401365

Wall.ie é um assistente web focado em reciclagem de lixo eletrônico, com chat integrado a um modelo de linguagem (Gemini), além de recursos embutidos como mapa de pontos de coleta, vídeos educativos, blogs e podcasts.

## Estrutura de Pastas

/Wall.ie/
├── login.html       ← Página de login
├── chat.html        ← Interface principal do chat Wallie
├── blogs.html       ← Lista de portais/blogs sobre reciclagem
├── script.js        ← Lógica de chat, integração com Gemini e embeds
└── README.md        ← Documentação (este arquivo)

## Principais Funcionalidades

1. **Login Simples (login.html)**
   - Exibe campos “Usuário” e “Senha” (fictícios).
   - Ao submeter, redireciona para `chat.html` (via JavaScript).
   - Não há backend real de autenticação; credenciais são apenas ilustrativas.

2. **Chat com API Gemini (chat.html + script.js)**
   - Após o login, exibe interface de chat:
     - Painel esquerdo com “Principais perguntas” (clicáveis).
     - Botões para:
       - **Ecoponto**: busca ecopontos próximos usando geolocalização.
       - **Vídeos**: embute playlist do YouTube em iframe.
       - **Blogs**: carrega `blogs.html` em iframe.
       - **Podcasts**: embute episódio do Spotify em iframe.
     - Painel direito mostra histórico de mensagens e campo de entrada para nova pergunta.
   - **Integração com Gemini v1beta**:
     - Ao enviar uma pergunta, `script.js` monta um JSON com todas as mensagens anteriores + uma instrução de sistema implícita:
       > “Você é um assistente que responde apenas sobre lixo eletrônico de forma curta e direta.  
       > Se a pergunta não for sobre esse tema, responda:  
       > ‘Desculpe, mas ajudo apenas com lixo eletrônico, quer tentar novamente?’.”
     - Envia via `fetch` (POST) para:
       ```
       https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key=<API_KEY>
       ```
     - Exibe “Wallie está digitando...” enquanto aguarda resposta.
     - Responde apenas dentro do escopo “lixo eletrônico” ou devolve a mensagem de recusa.
   - Histórico de conversas é mantido em memória no JavaScript e enviado a cada requisição para manter contexto.

3. **Embed de Conteúdos (iframe)**
   - **Ecoponto**: pede permissão de geolocalização do navegador. Se aceita, carrega Google Maps procurando “ecoponto perto de mim” nas coordenadas do usuário. Se negar, exibe busca genérica de “ecoponto”.
   - **Vídeos**: insere iframe com playlist de vídeos educativos no YouTube.
   - **Blogs**: carrega `blogs.html` dentro de um iframe para navegação em portais de reciclagem sem sair do Wall.ie.
   - **Podcasts**: insere iframe com episódio do Spotify sobre reciclagem de lixo eletrônico.
   - Botão **“Voltar para o Chat”** fecha o iframe e retorna à interface de chat.

4. **Página de Blogs (blogs.html)**
   - Lista portais/blogs parceiros (por exemplo: eCycle, CicloVivo).
   - Cada cartão contém:
     - Nome do site.
     - Descrição breve.
     - Botão “Abrir” que carrega o site correspondente dentro de um iframe no próprio `blogs.html`.
   - Botão “Voltar para o Chat” retorna para `chat.html`.

## Como Executar Corretamente

Para que a integração com Gemini funcione e não haja bloqueios de CORS, é **necessário** rodar esses arquivos via servidor HTTP ou servidor PHP. Basta seguir:

1. **Certifique-se** de ter o PHP instalado localmente (versão 7.4 ou superior).

2. **Abra um terminal/Prompt de Comando** dentro da pasta `/Wall.ie/`.

3. **Execute o servidor PHP**, apontando para `login.html`. Por exemplo:
   php -S localhost:8000


Isso fará com que todos os arquivos HTML, JS e chamadas à Gemini via fetch funcionem sem restrições de CORS.

4. **No navegador**, acesse:
   http://localhost:8000/login.html

   (ou apenas `http://localhost:8000/`, já que `login.html` é o índice padrão).

5. **Faça login** usando credenciais fictícias:

   * Usuário: `teste_user`
   * Senha: `senha123`
     (O login não é validado por backend; serve apenas para redirecionar para o chat.)

6. **Interaja com o Chat**:

   * Digite dúvidas sobre reciclagem de lixo eletrônico; o modelo Gemini responderá de forma curta e direta.

7. **Use os Botões Laterais**:

   * **Principais perguntas**: clique para repetir uma pergunta padrão no chat.
   * **Ecoponto**: carrega mapa do Google Maps buscando ecoponto perto de você (requer permissão de localização).
   * **Vídeos**: carrega playlist do YouTube em iframe.
   * **Blogs**: carrega `blogs.html` em iframe.
   * **Podcasts**: carrega episódio do Spotify em iframe.
   * Para retornar ao chat, clique em **“Voltar para o Chat”** no canto superior do iframe.

## Dependências

* **PHP 7.4+** (para rodar o servidor embutido e evitar bloqueios de CORS).
* **Navegador moderno** (Chrome, Firefox, Edge, Safari) com suporte a `fetch` e Geolocation API.
* **Chave de API Gemini** válida, já configurada em `script.js`:

  const API_KEY = "AIzaSyC8tVy1Yn0caUKESNH83bvku0-Pf0adozQ";

  Caso queira usar sua própria chave, edite o valor em `script.js`.


## Credenciais de Teste

* **Usuário:** `teste_user`
* **Senha:** `senha123`

Essas credenciais não realizam autenticação real; apenas provocam redirecionamento para `chat.html`.

## Observações

* **Versão Alfa: 0.0.1**
  Protótipo básico com chat e embeds.
* **Integração com Gemini**: feita diretamente em `script.js` via fetch para v1beta.
* **Escopo restrito**: Gemini responde somente sobre lixo eletrônico, de forma curta e direta.
* **Ecoponto**: funcionalidade que busca pontos de coleta próximos usando geolocalização.

## Histórico de Atualizações

1. **0.1 (Beta)**

   * Implementação de login estático (redireciona para chat).
   * Chat inicial com mensagem de boas-vindas.
   * Integração com Gemini v1beta (prompt de sistema implícito, formato `prompt.messages`).
   * Indicador “Wallie está digitando…” ao enviar pergunta.
   * **Ecoponto**: geolocalização e busca automática de ecopontos no Google Maps.
   * **Vídeos**: embed de playlist do YouTube.
   * **Blogs**: `blogs.html` para listar portais e carregar em iframe.
   * **Podcasts**: embed de episódio do Spotify.
   * Botões “Voltar para o Chat” para fechar iframe.
   * Ajustes de layout e estilos responsivos.

2. **Próximas Pendências**

   * Backend real de autenticação.
   * Coleta dinâmica de pontos via API de mapas (ex.: Google Places).
   * Fine-tuning de modelo ou treinamento próprio de linguagem.
   * Melhoria geral de UX/UI, gerenciamento de sessão, segurança.

