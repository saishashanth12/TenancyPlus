<!-- chatbot.php -->
<!-- This file contains the HTML, CSS, and JavaScript for the AI chatbot. -->

<style>
    /* Styles for the floating chat button */
    .chat-fab {
        position: fixed;
        bottom: 30px;
        right: 30px;
        background-color: var(--primary-color);
        color: white;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 28px;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 1000;
        transition: transform 0.2s;
    }

    .chat-fab:hover {
        transform: scale(1.1);
    }

    /* Styles for the chat widget window */
    .chat-widget {
        position: fixed;
        bottom: 100px;
        right: 30px;
        width: 350px;
        max-width: 90%;
        background-color: white;
        border-radius: 1rem;
        box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        z-index: 1000;
        /* Start hidden */
        transform: scale(0);
        transform-origin: bottom right;
        transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .chat-widget.open {
        transform: scale(1); /* Animate to full size */
    }

    .chat-header {
        background-color: var(--primary-color);
        color: white;
        padding: 1rem;
        font-weight: 600;
    }

    .chat-messages {
        flex-grow: 1;
        padding: 1rem;
        overflow-y: auto;
        height: 300px;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .message {
        padding: 0.5rem 1rem;
        border-radius: 1rem;
        max-width: 80%;
        word-wrap: break-word;
    }
    
    .message.user {
        background-color: #EBF4FF;
        color: #1E40AF;
        align-self: flex-end;
        border-bottom-right-radius: 0;
    }

    .message.bot {
        background-color: #F3F4F6;
        color: #374151;
        align-self: flex-start;
        border-bottom-left-radius: 0;
    }
     .message.bot.thinking {
        font-style: italic;
        color: #6B7280;
    }


    .chat-input-form {
        display: flex;
        border-top: 1px solid var(--border-color);
    }

    .chat-input {
        flex-grow: 1;
        border: none;
        padding: 1rem;
        font-size: 1rem;
        outline: none;
    }

    .chat-submit {
        background: var(--primary-color);
        color: white;
        border: none;
        padding: 0 1.25rem;
        cursor: pointer;
        font-weight: 600;
    }
</style>

<!-- HTML structure for the chatbot -->
<div class="chat-fab" id="chat-fab">
    <!-- Simple chat bubble SVG icon -->
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
</div>

<div class="chat-widget" id="chat-widget">
    <div class="chat-header">Tenancy+ AI Assistant</div>
    <div class="chat-messages" id="chat-messages">
        <div class="message bot">Hello! How can I help you today?</div>
    </div>
    <form class="chat-input-form" id="chat-form">
        <input type="text" class="chat-input" id="chat-input" placeholder="Ask a question..." autocomplete="off">
        <button type="submit" class="chat-submit">Send</button>
    </form>
</div>


<script>
    // JavaScript to power the chatbot
    const chatFab = document.getElementById('chat-fab');
    const chatWidget = document.getElementById('chat-widget');
    const chatForm = document.getElementById('chat-form');
    const chatInput = document.getElementById('chat-input');
    const chatMessages = document.getElementById('chat-messages');

    // Toggle chat widget visibility
    chatFab.addEventListener('click', () => {
        chatWidget.classList.toggle('open');
    });

    // Handle form submission
    chatForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const userMessage = chatInput.value.trim();
        if (userMessage === '') return;

        // Display user's message
        addMessage(userMessage, 'user');
        chatInput.value = '';

        // Show a "thinking" message from the bot
        const thinkingMessage = addMessage('Thinking...', 'bot thinking');

        try {
            // Call the AI model
            const botResponse = await getAiResponse(userMessage);
            // Replace "thinking" message with the actual response
            thinkingMessage.textContent = botResponse;
            thinkingMessage.classList.remove('thinking');
        } catch (error) {
            thinkingMessage.textContent = 'Sorry, I encountered an error. Please try again.';
            thinkingMessage.classList.remove('thinking');
            console.error("AI Error:", error);
        }
    });

    // Helper function to add a message to the chat window
    function addMessage(text, type) {
        const messageElement = document.createElement('div');
        const classes = type.split(' ');
        messageElement.classList.add('message', ...classes);
        messageElement.textContent = text;
        chatMessages.appendChild(messageElement);
        chatMessages.scrollTop = chatMessages.scrollHeight;
        return messageElement;
    }

    // --- Gemini API Call ---
    async function getAiResponse(prompt) {
        const apiKey = "AIzaSyCOjrmtpwxBvCKJPp30fDRbAF2JnRp0etc"; // Make sure your API key is pasted here
        
        // THE FIX IS HERE: Updated the model name to the correct, supported one.
        const apiUrl = `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-preview-09-2025:generateContent?key=${apiKey}`;

        const payload = {
            contents: [{
                parts: [{ text: prompt }]
            }]
        };

        const response = await fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        if (!response.ok) {
            const errorBody = await response.json();
            console.error("API Error Response:", errorBody);
            throw new Error(`API request failed with status ${response.status}: ${errorBody.error.message}`);
        }

        const data = await response.json();
        
        if (data.candidates && data.candidates[0] && data.candidates[0].content.parts[0].text) {
             return data.candidates[0].content.parts[0].text;
        } else {
            return "I'm not sure how to respond to that.";
        }
    }
</script>

