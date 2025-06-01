
/*
Integrantes do grupo:
- Rodrigo Lucas – 10365071
- Vitor Machado – 10409358
- Vinícius Magno – 10401365

RESUMO DE FUNCIONALIDADES:
- Contém toda a lógica de interface do chat Wallie e integração com a API Gemini.
- Ao carregar (DOMContentLoaded):
    • Pega referências de elementos (#chatWindow, #chatForm, #userInput, etc.).
    • Exibe mensagem inicial do assistente e salva no histórico “conversation”.
    • Intercepta submit do formulário para processar entrada do usuário.
    • Percorre cards de “Principais perguntas” para repetir pergunta no chat.
- Funções principais:
    • processUserMessage(text): adiciona mensagem do usuário no DOM, armazena em “conversation”, exibe indicador de “Wallie está digitando…” e chama sendToGeminiAPI().
    • sendToGeminiAPI(): monta JSON com todos os itens de “conversation”, faz fetch POST para endpoint Gemini (gemini-1.5-flash-latest), recebe resposta, retira indicador de loading e exibe mensagem do assistente no chat.
    • addMessageToChat(role, text): cria dinamicamente elementos DIV para usuário (“.user”) e bot (“.bot”), garantindo rolagem automática.
- Funções de embed de conteúdo (iframe):
    • openEcoponto(): oculta chat, carrega mapa do Google Maps apontando para ecoponto local, exibe iframe.
    • openVideos(): oculta chat, carrega playlist de vídeos educacionais do YouTube em iframe.
    • openBlogs(): oculta chat, carrega blogs.html em iframe.
    • openPodcasts(): oculta chat, carrega episódio de podcast do Spotify em iframe.
    • closeIframe(): limpa src do iframe e exibe novamente chat e formulário.
- Responsabilidade de IDs/CSS no HTML deve corresponder ao script para funcionar corretamente.
*/

// ===== Integração com API Gemini =====
const API_KEY = "AIzaSyC8tVy1Yn0caUKESNH83bvku0-Pf0adozQ";
const API_URL = `https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key=${API_KEY}`;

// ===== Funções de Chat e UI Wallie =====
document.addEventListener("DOMContentLoaded", function () {
  // Elementos do Chat (IDs devem bater com o HTML)
  const chatWindow = document.getElementById("chatWindow");
  const chatForm = document.getElementById("chatForm");
  const userInput = document.getElementById("userInput");

  // Elementos do Iframe (Ecoponto, Vídeos, Blogs, Podcasts)
  const iframeContainer = document.getElementById("iframeContainer");
  const embeddedFrame = document.getElementById("embeddedFrame");

  // Histórico de conversa para enviar à API
  const conversation = [];

  // Mensagem inicial do assistente
  addMessageToChat("assistant", "Olá! Eu sou o Wallie, seu assistente virtual. Em que posso ajudar hoje?");
  conversation.push({ role: "assistant", text: "Olá! Eu sou o Wallie, seu assistente virtual. Em que posso ajudar hoje?" });

  // Envio de mensagem: formulário
  chatForm.addEventListener("submit", function (e) {
    e.preventDefault();
    const message = userInput.value.trim();
    if (!message) return;

    processUserMessage(message);
    userInput.value = "";
  });

  // Botões “Principais perguntas” (se existirem no HTML)
  const questionCards = document.querySelectorAll(".question-card");
  questionCards.forEach(function (card) {
    card.addEventListener("click", function () {
      const pergunta = card.getAttribute("data-question");
      processUserMessage(pergunta);
    });
  });

  // ===== Processa entrada do usuário no chat =====
  function processUserMessage(text) {
    addMessageToChat("user", text);
    conversation.push({ role: "user", text: text });
    addLoadingIndicator();
    sendToGeminiAPI();
  }

  // Adiciona indicador de "digitando..."
  function addLoadingIndicator() {
    const loadingDiv = document.createElement("div");
    loadingDiv.classList.add("message", "assistant-message");
    loadingDiv.id = "loadingIndicator";
    loadingDiv.textContent = "Wallie está digitando...";
    chatWindow.appendChild(loadingDiv);
    chatWindow.scrollTop = chatWindow.scrollHeight;
  }

  // Remove indicador de carregamento
  function removeLoadingIndicator() {
    const loadingDiv = document.getElementById("loadingIndicator");
    if (loadingDiv) {
      chatWindow.removeChild(loadingDiv);
    }
  }

  // Chama a API Gemini com o histórico de conversa
  async function sendToGeminiAPI() {
    // Prepara body conforme esperado pela API
    const requestBody = {
      contents: conversation.map((msg) => ({
        role: msg.role === "assistant" ? "model" : "user",
        parts: [{ text: msg.text }],
      })),
    };

    try {
      const response = await fetch(API_URL, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(requestBody),
      });

      if (!response.ok) {
        throw new Error(`Erro da API: ${response.status} ${response.statusText}`);
      }

      const data = await response.json();
      const parts = data?.candidates?.[0]?.content?.parts;
      let assistantText = "";

      if (parts && parts.length > 0) {
        assistantText = parts.map((p) => p.text).join("");
      } else {
        assistantText = "[Erro: resposta inválida da API]";
      }

      removeLoadingIndicator();
      addMessageToChat("assistant", assistantText);
      conversation.push({ role: "assistant", text: assistantText });
    } catch (err) {
      removeLoadingIndicator();
      addMessageToChat("assistant", `❌ Erro ao obter resposta: ${err.message}`);
      console.error("API Gemini error:", err);
    }
  }

  // Adiciona mensagem (usuário ou assistente) ao DOM
  function addMessageToChat(role, text) {
    const messageDiv = document.createElement("div");
    messageDiv.classList.add("message");
    if (role === "user") {
      messageDiv.classList.add("user");
    } else {
      messageDiv.classList.add("bot");
    }
    const messageText = document.createElement("div");
    messageText.classList.add("message-text");
    messageText.innerText = text;
    messageDiv.appendChild(messageText);
    chatWindow.appendChild(messageDiv);
    chatWindow.scrollTop = chatWindow.scrollHeight;
  }

  // ===== Funções de Embedding de Conteúdo =====

  // Ecoponto (Google Maps)
  window.openEcoponto = function () {
    chatWindow.style.display = "none";
    chatForm.style.display = "none";
    embeddedFrame.src =
      "https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3165.8323175661254!2d-47.07296158469214!3d-22.904009980995983!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x94c8bd2cfb8bcfab%3A0x9fc4e4a4670912ad!2sEcoponto!5e0!3m2!1spt-BR!2sbr!4v1700000000000!5m2!1spt-BR!2sbr";
    iframeContainer.style.display = "block";
  };

  // Vídeos (YouTube playlist)
  window.openVideos = function () {
    chatWindow.style.display = "none";
    chatForm.style.display = "none";
    embeddedFrame.src = "https://www.youtube.com/embed/videoseries?list=PL6kYFqiFAO53ZvLXtDqYI-qAi7UBFj39s";
    iframeContainer.style.display = "block";
  };

  // Blogs (carrega blogs.html)
  window.openBlogs = function () {
    chatWindow.style.display = "none";
    chatForm.style.display = "none";
    embeddedFrame.src = "blogs.html";
    iframeContainer.style.display = "block";
  };

  // Podcasts (Spotify)
  window.openPodcasts = function () {
    chatWindow.style.display = "none";
    chatForm.style.display = "none";
    embeddedFrame.src = "https://open.spotify.com/embed/episode/2arxh4XyyVxXUeg0DzvSrf";
    iframeContainer.style.display = "block";
  };

  // Voltar ao chat (fecha iframe)
  window.closeIframe = function () {
    embeddedFrame.src = "";
    iframeContainer.style.display = "none";
    chatWindow.style.display = "block";
    chatForm.style.display = "flex";
  };
});
