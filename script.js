document.addEventListener('DOMContentLoaded', function() {
  const chatWindow = document.getElementById('chatWindow');
  const chatForm = document.getElementById('chatForm');
  const userInput = document.getElementById('userInput');

  // Ajuste estes prompts de acordo com sua página/tópico
  let systemPrompt = 'Você é um assistente especializado em verificar informações e esclarecer dúvidas sobre fake news.';
  let assistantInitialMessage = 'Olá! Eu sou o FakeNews Detector AI. Como posso ajudá-lo hoje?';

  // Exemplo de mensagem inicial (caso queira exibir automaticamente)
  if (assistantInitialMessage) {
      addMessageToChat('bot', assistantInitialMessage);
  }

  chatForm.addEventListener('submit', function(e) {
      e.preventDefault();
      const message = userInput.value.trim();
      if (message) {
          // Adiciona a mensagem do usuário ao chat
          addMessageToChat('user', message);
          userInput.value = '';
          
          // Chama a função que faz a requisição à API
          getBotResponse(message, systemPrompt)
            .then(botMessage => {
              addMessageToChat('bot', botMessage);
            })
            .catch(error => {
              console.error('Erro:', error);
              addMessageToChat('bot', 'Desculpe, ocorreu um erro ao obter a resposta.');
            });
      }
  });

  // Função que insere a mensagem no chat
  function addMessageToChat(sender, text) {
      const messageDiv = document.createElement('div');
      messageDiv.classList.add('message', sender);
      
      const messageText = document.createElement('div');
      messageText.classList.add('message-text');
      messageText.innerHTML = convertMarkdownToHTML(text);
      
      messageDiv.appendChild(messageText);
      chatWindow.appendChild(messageDiv);
      chatWindow.scrollTop = chatWindow.scrollHeight;
  }

  // Exemplo de conversão simples de Markdown para HTML
  function convertMarkdownToHTML(markdown) {
      return markdown
          .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>') // **texto** -> negrito
          .replace(/\*(.*?)\*/g, '<em>$1</em>');           // *texto*  -> itálico
  }

  /**
   * Faz a chamada à API PaLM / Generative Language (chat-bison-001).
   * @param {string} userMessage Mensagem do usuário
   * @param {string} systemPrompt Prompt de sistema (contexto/instruções)
   * @returns {Promise<string>} Resposta do modelo
   */
  async function getBotResponse(userMessage, systemPrompt) {
      // Substitua pela sua própria API Key
      const API_KEY = 'AIzaSyC8tVy1Yn0caUKESNH83bvku0-Pf0adozQ';

      // Endpoint oficial para gerar mensagens de chat
      const API_ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta2/models/chat-bison-001:generateMessage';

      // Monta o corpo da requisição no formato aceito pela API
      const requestBody = {
        prompt: {
          // Adicionamos o "systemPrompt" como uma mensagem do autor "system"
          messages: [
            {
              author: 'system',
              content: systemPrompt
            },
            {
              author: 'user',
              content: userMessage
            }
          ]
        },
        // Configurações opcionais, como número de candidatos, temperatura, etc.
        temperature: 0.2,
        candidateCount: 1
      };

      try {
          const response = await fetch(`${API_ENDPOINT}?key=${API_KEY}`, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json'
              },
              body: JSON.stringify(requestBody)
          });

          if (!response.ok) {
              const errData = await response.json();
              console.error('Erro da API:', errData);
              throw new Error(errData.error?.message || 'Erro na requisição');
          }

          const data = await response.json();
          // Normalmente, a resposta vem em data.candidates[0].content
          if (data.candidates && data.candidates.length > 0) {
              return data.candidates[0].content;
          } else {
              throw new Error('Nenhuma resposta retornada pela API.');
          }
      } catch (error) {
          console.error('Erro de comunicação com a API:', error);
          throw error;
      }
  }
});
