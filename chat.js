const header = document.getElementById('header');

if (header) {
  window.addEventListener('scroll', () => {
    header.classList.toggle('scrolled', window.scrollY > 40);
  });
}

window.addEventListener('load', () => {
  const heroBg = document.getElementById('heroBg');

  if (heroBg) {
    heroBg.classList.add('loaded');
  }
});

const revealObserver = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.classList.add('visible');
    }
  });
}, { threshold: 0.15 });

document.querySelectorAll('.reveal').forEach(el => {
  revealObserver.observe(el);
});

window.addEventListener('scroll', () => {
  const bg = document.getElementById('heroBg');

  if (bg) {
    const scrolled = window.scrollY;
    bg.style.transform = `scale(1) translateY(${scrolled * 0.18}px)`;
  }
}, { passive: true });

const chatBox = document.getElementById('chatBox');
const messageForm = document.getElementById('messageForm');
const messageInput = document.getElementById('messageInput');

let lastMessageId = 0;
let isFetching = false;

function isNearBottom() {
  if (!chatBox) return true;

  return chatBox.scrollHeight - chatBox.scrollTop - chatBox.clientHeight < 120;
}

function scrollToBottom() {
  if (!chatBox) return;

  chatBox.scrollTop = chatBox.scrollHeight;
}

function createMessageElement(message) {
  const isMine = parseInt(message.sender_id, 10) === parseInt(window.CHAT_CONFIG.currentUserId, 10);

  const row = document.createElement('div');
  row.className = isMine ? 'message-row mine' : 'message-row theirs';

  const bubble = document.createElement('div');
  bubble.className = 'message-bubble';

  const meta = document.createElement('div');
  meta.className = 'message-meta';
  meta.textContent = isMine ? 'You' : message.sender_name;

  const text = document.createElement('div');
  text.className = 'message-text';
  text.textContent = message.message;

  const time = document.createElement('div');
  time.className = 'message-time';
  time.textContent = message.created_at;

  bubble.appendChild(meta);
  bubble.appendChild(text);
  bubble.appendChild(time);

  row.appendChild(bubble);

  return row;
}

async function fetchMessages() {
  if (!window.CHAT_CONFIG || !window.CHAT_CONFIG.threadId || !chatBox || isFetching) {
    return;
  }

  isFetching = true;

  try {
    const response = await fetch(
      `fetch_messages.php?thread_id=${window.CHAT_CONFIG.threadId}&after_id=${lastMessageId}`
    );

    const data = await response.json();

    if (!data.success) {
      isFetching = false;
      return;
    }

    const shouldScroll = isNearBottom();

    const loading = chatBox.querySelector('.loading-messages');

    if (loading) {
      loading.remove();
    }

    data.messages.forEach(message => {
      const messageEl = createMessageElement(message);
      chatBox.appendChild(messageEl);
      lastMessageId = Math.max(lastMessageId, parseInt(message.id, 10));
    });

    if (shouldScroll || data.messages.length > 0) {
      scrollToBottom();
    }

  } catch (error) {
    console.error('Fetch messages error:', error);
  }

  isFetching = false;
}

if (messageForm && messageInput) {
  messageForm.addEventListener('submit', async (event) => {
    event.preventDefault();

    const message = messageInput.value.trim();

    if (!message) {
      return;
    }

    const button = messageForm.querySelector('button');
    button.disabled = true;

    const formData = new FormData();
    formData.append('thread_id', window.CHAT_CONFIG.threadId);
    formData.append('message', message);

    try {
      const response = await fetch('send_message.php', {
        method: 'POST',
        body: formData
      });

      const data = await response.json();

      if (data.success) {
        messageInput.value = '';
        await fetchMessages();
        scrollToBottom();
      } else {
        alert(data.message || 'Failed to send message.');
      }

    } catch (error) {
      console.error('Send message error:', error);
      alert('Failed to send message.');
    }

    button.disabled = false;
    messageInput.focus();
  });

  messageInput.addEventListener('keydown', (event) => {
    if (event.key === 'Enter' && !event.shiftKey) {
      event.preventDefault();
      messageForm.dispatchEvent(new Event('submit'));
    }
  });
}

fetchMessages();
setInterval(fetchMessages, 15000);