document.addEventListener("DOMContentLoaded", function () {
  console.log("Chat.js script loaded at: " + new Date().toLocaleTimeString());

  let chatSession = null;
  let lastMessageId = 0;
  let chatInterval = null;
  const chatButton = document.createElement("div");
  chatButton.id = "chat-button";
  chatButton.innerHTML = `
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
    </svg>
  `;
  chatButton.className = "chat-button";

  const chatBox = document.createElement("div");
  chatBox.id = "chat-box";
  chatBox.className = "chat-box hidden";
  chatBox.innerHTML = `
        <div class="chat-header">
            <h3>
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;">
                <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
              </svg>
              Support Chat
            </h3>
            <span class="close-chat">&times;</span>
        </div>
        <div class="chat-messages"></div>
        <div class="chat-input">
            <textarea placeholder="Type your message..."></textarea>
            <button class="send-message">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="22" y1="2" x2="11" y2="13"></line>
                <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
              </svg>
            </button>
        </div>
    `;
  document.body.appendChild(chatButton);
  document.body.appendChild(chatBox);

  chatButton.addEventListener("click", function () {
    chatBox.classList.remove("hidden");
    chatButton.classList.add("hidden");

    if (!chatSession) {
      initializeChat();
    }
  });
  chatBox.querySelector(".close-chat").addEventListener("click", function () {
    chatBox.classList.add("hidden");
    chatButton.classList.remove("hidden");
    if (chatInterval) {
      clearInterval(chatInterval);
      chatInterval = null;
    }
  });
  chatBox.querySelector(".send-message").addEventListener("click", sendMessage);
  chatBox.querySelector("textarea").addEventListener("keypress", function (e) {
    if (e.key === "Enter" && !e.shiftKey) {
      e.preventDefault();
      sendMessage();
    }
  });
  function showLoadingMessage() {
    const chatMessages = chatBox.querySelector(".chat-messages");
    const messageDiv = document.createElement("div");
    messageDiv.className = "message admin-message";
    messageDiv.innerHTML = `
      Connecting to support... 
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="spinner">
        <circle cx="12" cy="12" r="10"></circle>
        <path d="M12 6v6l4 2"></path>
      </svg>
    `;
    chatMessages.appendChild(messageDiv);
  }
  function initializeChat() {
    showLoadingMessage();

    const xhr = new XMLHttpRequest();
    xhr.open("POST", "/includes/chat_handler.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function () {
      if (xhr.readyState === 4) {
        if (xhr.status === 200) {
          try {
            const response = JSON.parse(xhr.responseText);
            if (response.success) {
              chatSession = response.session_id;
              startMessageChecking();
            } else {
              showError(response.error || "Failed to initialize chat");
            }
          } catch (e) {
            showError("Error parsing server response");
            console.error("Chat error:", e, xhr.responseText);
          }
        } else {
          showError("Server error. Please try again later.");
        }
      }
    };
    xhr.send("action=init");
  }
  function sendMessage() {
    const textarea = chatBox.querySelector("textarea");
    const message = textarea.value.trim();

    if (!message) return;

    if (!chatSession) {
      showError("Chat session not initialized");
      return;
    }
    const messageToSend = message;
    textarea.value = "";

    const xhr = new XMLHttpRequest();
    xhr.open("POST", "/includes/chat_handler.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function () {
      if (xhr.readyState === 4) {
        if (xhr.status === 200) {
          try {
            const response = JSON.parse(xhr.responseText);
            if (response.success) {
              addMessageToChat(messageToSend, "user");
              if (response.message_id) {
                lastMessageId = response.message_id;
              }
              if (chatInterval) {
                clearInterval(chatInterval);
              }
              startMessageChecking();
            } else {
              showError(response.error || "Failed to send message");
            }
          } catch (e) {
            showError("Error parsing server response");
            console.error("Chat error:", e, xhr.responseText);
          }
        } else {
          showError("Server error. Please try again later.");
        }
      }
    };
    xhr.send(
      `action=send&message=${encodeURIComponent(
        messageToSend
      )}&session_id=${encodeURIComponent(chatSession)}`
    );
  }

  function startMessageChecking() {
    checkNewMessages();
    chatInterval = setInterval(checkNewMessages, 5000); 
  }

  function checkNewMessages() {
    if (!chatSession) return;

    const xhr = new XMLHttpRequest();
    xhr.open("POST", "/includes/chat_handler.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function () {
      if (xhr.readyState === 4 && xhr.status === 200) {
        try {
          const response = JSON.parse(xhr.responseText);
          if (
            response.success &&
            response.messages &&
            response.messages.length > 0
          ) {
            const newMessages = response.messages.filter(
              (msg) => msg.id > lastMessageId
            );
            if (newMessages.length > 0) {
              displayNewMessages(newMessages);
            }
          }
        } catch (e) {
          console.error("Error checking messages:", e);
        }
      }
    };
    xhr.send(
      `action=get&session_id=${encodeURIComponent(
        chatSession
      )}&last_id=${lastMessageId}`
    );
  }
  function displayNewMessages(messages) {
    if (!messages.length) return;

    const chatMessages = chatBox.querySelector(".chat-messages");

    const loadingMessage = chatMessages.querySelector(".message:first-child");
    if (
      loadingMessage &&
      loadingMessage.innerHTML.includes("Connecting to support")
    ) {
      loadingMessage.remove();
    }
    const processedIds = new Set();

    messages.forEach((msg) => {
      if (processedIds.has(msg.id)) return;
      processedIds.add(msg.id);

      if (msg.is_from_user && msg.id <= lastMessageId) return;

      const sender = msg.is_from_user ? "user" : "admin";
      addMessageToChat(msg.message, sender);

      if (msg.id > lastMessageId) {
        lastMessageId = msg.id;
      }
    });
    chatMessages.scrollTop = chatMessages.scrollHeight;
  }

  function addMessageToChat(text, sender) {
    const chatMessages = chatBox.querySelector(".chat-messages");
    const messageDiv = document.createElement("div");
    messageDiv.className = `message ${sender}-message`;
    messageDiv.textContent = text;
    chatMessages.appendChild(messageDiv);

    chatMessages.scrollTop = chatMessages.scrollHeight;
  }

  function showError(message) {
    const chatMessages = chatBox.querySelector(".chat-messages");
    const errorDiv = document.createElement("div");
    errorDiv.className = "message error-message";
    errorDiv.textContent = message;
    chatMessages.appendChild(errorDiv);

    chatMessages.scrollTop = chatMessages.scrollHeight;

    console.error("Chat error:", message);
  }
});
