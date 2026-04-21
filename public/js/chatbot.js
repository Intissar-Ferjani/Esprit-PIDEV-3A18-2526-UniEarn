function initChatbot() {
    const openBtn = document.getElementById('open-chatbot');
    const closeBtn = document.getElementById('close-chatbot');
    const chatbotWindow = document.getElementById('chatbot-window');
    const msgContainer = document.getElementById('chatbot-messages');
    const input = document.getElementById('chatbot-input');
    const sendBtn = document.getElementById('send-chatbot-msg');

    if (!openBtn || openBtn.dataset.chatbotInitialized) return;
    openBtn.dataset.chatbotInitialized = 'true'; // Prevent duplicate listeners

    openBtn.addEventListener('click', () => {
        chatbotWindow.classList.toggle('hidden');
        document.querySelector('.bubble-notification').classList.add('hidden');
    });

    closeBtn.addEventListener('click', () => {
        chatbotWindow.classList.add('hidden');
    });

    function addMessage(text, type) {
        const msgDiv = document.createElement('div');
        msgDiv.className = `message ${type}-message`;
        msgDiv.textContent = text;
        msgContainer.appendChild(msgDiv);
        msgContainer.scrollTop = msgContainer.scrollHeight;
    }

    function addTypingIndicator() {
        const indicator = document.createElement('div');
        indicator.id = 'typing-indicator';
        indicator.className = 'typing-indicator';
        indicator.innerHTML = '<div class="dot"></div><div class="dot"></div><div class="dot"></div>';
        msgContainer.appendChild(indicator);
        msgContainer.scrollTop = msgContainer.scrollHeight;
        return indicator;
    }

    async function sendMessage() {
        const message = input.value.trim();
        if (!message) return;

        addMessage(message, 'user');
        input.value = '';

        const indicator = addTypingIndicator();

        try {
            const response = await fetch('/chatbot/message', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ message: message })
            });

            const data = await response.json();
            indicator.remove();

            if (data.response) {
                addMessage(data.response, 'bot');
            } else {
                addMessage("Erreur: Impossible de joindre l'IA.", 'bot');
            }
        } catch (error) {
            indicator.remove();
            addMessage("Erreur de connexion.", 'bot');
            console.error('Error:', error);
        }
    }

    sendBtn.addEventListener('click', sendMessage);
    input.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') sendMessage();
    });
}

document.addEventListener('DOMContentLoaded', initChatbot);
document.addEventListener('turbo:load', initChatbot);