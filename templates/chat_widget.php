<?php
?>

<link rel="icon" href="/images/logo.png" type="image/png">
<link rel="shortcut icon" href="/images/logo.png" type="image/png">

<div id="chat-widget-container">
    <div class="chat-icon">
        <i class="fas fa-comments"></i>
        <span class="notification-badge">1</span>
    </div>
    
    <div class="chat-window">
        <div class="chat-header">
            <h3>Theatre Support</h3>
            <button class="chat-close"><i class="fas fa-times"></i></button>
        </div>
        
        <div class="chat-messages">
            <div class="message admin-message">
                <div class="message-sender">Support</div>
                <div class="message-content">Welcome to our support chat! How can we help you today?</div>
            </div>
        </div>
        
        <div class="chat-input">
            <input type="text" placeholder="Type your message here...">
            <button id="send-message-btn"><i class="fas fa-paper-plane"></i></button>
        </div>
    </div>
</div>

<link rel="stylesheet" href="/css/chat-widget.css">
<script src="/js/chat-widget.js"></script>
<style>
    #chat-widget-container .chat-messages {
        height: 300px;
        overflow-y: auto;
        padding: 15px;
        background-color: #f8f9fa;
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    
    .message {
        max-width: 85%;
        clear: both;
        margin-bottom: 10px;
        position: relative;
    }
    
    .admin-message {
        align-self: flex-start;
    }
    
    .user-message {
        align-self: flex-end;
    }
    
    .message-content {
        padding: 12px 15px;
        border-radius: 18px;
        word-wrap: break-word;
    }
    
    .admin-message .message-content {
        background-color: #e9ecef;
        color: #212529;
    }
    
    .user-message .message-content {
        background-color: #007bff;
        color: white;
    }
    
    .message-sender {
        font-size: 12px;
        font-weight: bold;
        margin-bottom: 5px;
    }
    
    .admin-message .message-sender {
        color: #0056b3;
    }
    
    .user-message .message-sender {
        color: #28a745;
        text-align: right;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sendButton = document.getElementById('send-message-btn');
    const chatInput = document.querySelector('.chat-input input');
    const chatMessages = document.querySelector('.chat-messages');
    
    if (window.chatInitialized) {
        console.log('Chat already initialized by external script');
        return;
    }
    window.chatInitialized = true;
    
    function loadMessages() {
        const savedMessages = localStorage.getItem('chatMessages');
        if (savedMessages) {
            try {
                chatMessages.innerHTML = '';
                
                const messages = JSON.parse(savedMessages);
                messages.forEach(msg => {
                    const messageDiv = document.createElement('div');
                    messageDiv.className = `message ${msg.type}-message`;
                    messageDiv.innerHTML = `
                        <div class="message-sender">${msg.sender}</div>
                        <div class="message-content">${msg.content}</div>
                    `;
                    chatMessages.appendChild(messageDiv);
                });
                
                chatMessages.scrollTop = chatMessages.scrollHeight;
            } catch (e) {
                console.error('Error loading saved messages:', e);
            }
        }
    }
    
    function saveMessages() {
        const messageElements = chatMessages.querySelectorAll('.message');
        const messages = [];
        
        messageElements.forEach(el => {
            const isAdmin = el.classList.contains('admin-message');
            const sender = el.querySelector('.message-sender').textContent;
            const content = el.querySelector('.message-content').textContent;
            
            messages.push({
                type: isAdmin ? 'admin' : 'user',
                sender: sender,
                content: content
            });
        });
        
        localStorage.setItem('chatMessages', JSON.stringify(messages));
    }
    loadMessages();
    
    function sendTestMessage(e) {
        if (e && e.preventDefault) {
            e.preventDefault();
        }
        
        const messageText = chatInput.value.trim();
        if (messageText) {
            const userMessage = document.createElement('div');
            userMessage.className = 'message user-message';
            userMessage.innerHTML = `
                <div class="message-sender">You</div>
                <div class="message-content">${messageText}</div>
            `;
            chatMessages.appendChild(userMessage);
            
            chatInput.value = '';
            
            chatMessages.scrollTop = chatMessages.scrollHeight;
            
            saveMessages();
            
            setTimeout(function() {
                const adminMessage = document.createElement('div');
                adminMessage.className = 'message admin-message';
                adminMessage.innerHTML = `
                    <div class="message-sender">Support</div>
                    <div class="message-content">Thanks for your message! Our team will respond shortly.</div>
                `;
                chatMessages.appendChild(adminMessage);
                chatMessages.scrollTop = chatMessages.scrollHeight;
                
                saveMessages();
                
                setTimeout(function() {
                    window.location.reload();
                }, 500);
            }, 1000);
        }
    }
    const newSendButton = sendButton.cloneNode(true);
    sendButton.parentNode.replaceChild(newSendButton, sendButton);
    
    const newChatInput = chatInput.cloneNode(true);
    chatInput.parentNode.replaceChild(newChatInput, chatInput);
    
    newSendButton.addEventListener('click', sendTestMessage);
    newChatInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            sendTestMessage(e);
        }
    });
    const chatIcon = document.querySelector('.chat-icon');
    chatIcon.addEventListener('click', function() {
        document.querySelector('.chat-window').classList.add('active');
    });
    
    document.querySelector('.chat-close').addEventListener('click', function() {
        document.querySelector('.chat-window').classList.remove('active');
    });
});
</script> 