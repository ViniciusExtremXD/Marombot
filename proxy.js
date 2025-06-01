
/*
Integrantes do grupo:
- Rodrigo Lucas – 10365071
- Vitor Machado – 10409358
- Vinícius Magno – 10401365

RESUMO DE FUNCIONALIDADES:
- Servidor Node.js (Express) que atua como proxy para requisições à API Gemini.
- Recebe POST em /chat com { mensagens: [...] } no corpo.
- Monta payload necessário e encaminha para o endpoint Gemini: 
  https://gemini.googleapis.com/v1/models/gemini-1:generateMessage?key=<API_KEY>
- Retorna a resposta da Gemini ao cliente.
- Utiliza node-fetch para executar a chamada externa à API.
- Executar: `npm install express node-fetch` antes de iniciar.
*/

// Instale: npm install express node-fetch
const express = require("express");
const fetch = require("node-fetch");
const app = express();
app.use(express.json());

app.post("/chat", async (req, res) => {
  const { mensagens } = req.body;
  const body = {
    prompt: { messages: mensagens },
    temperature: 0.2,
    candidateCount: 1,
  };

  try {
    const apiRes = await fetch(
      "https://gemini.googleapis.com/v1/models/gemini-1:generateMessage?key=AIzaSyC8tVy1Yn0caUKESNH83bvku0-Pf0adozQ",
      {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(body),
      }
    );
    const data = await apiRes.json();
    res.json(data);
  } catch (err) {
    res.status(500).json({ erro: err.message });
  }
});

app.listen(3000, () => {
  console.log("Proxy rodando em http://localhost:3000");
});
