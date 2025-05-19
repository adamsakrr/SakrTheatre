
document.addEventListener("DOMContentLoaded", function () {
  const adminMessages = document.querySelectorAll(".admin-message .sender");
  adminMessages.forEach(function (element) {
    element.innerHTML = '<i class="fas fa-user-shield"></i> Admin';
  });
  const chatMessages = document.querySelector(".chat-messages");
  if (chatMessages) {
    chatMessages.scrollTop = chatMessages.scrollHeight;
  }
  const messageInput = document.getElementById("message");
  if (messageInput) {
    messageInput.focus();
  }
});
