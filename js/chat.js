class ChatUI {
    static API_BASE_URL = 'http://localhost:8888/ai_analytics_dashboard/api';

    constructor() {
        // UI Elements
        this.messageContainer = document.querySelector('#chat-messages');
        this.messageInput = document.querySelector('#message-input');
        this.sendButton = document.querySelector('#send-button');
        this.typingIndicator = document.querySelector('#typing-indicator');
        this.errorMessage = document.querySelector('#error-message');
        this.connectionStatus = document.querySelector('#connection-status');

        // Bind event listeners
        this.messageInput?.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        });

        this.messageInput?.addEventListener('input', () => {
            this.adjustTextareaHeight();
        });

        this.sendButton?.addEventListener('click', () => {
            this.sendMessage();
        });

        // Check authentication on load
        this.checkAuthentication();
    }

    // Check if user is authenticated
    async checkAuthentication() {
        const token = localStorage.getItem('token');
        if (!token) {
            window.location.href = 'login.html';
            return;
        }

        try {
            const response = await fetch(`${ChatUI.API_BASE_URL}/verify_token.php`, {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });

            if (!response.ok) {
                throw new Error('Token verification failed');
            }

            // Test API connection
            await this.testConnection();

        } catch (error) {
            console.error('Authentication error:', error);
            localStorage.removeItem('token');
            window.location.href = 'login.html';
        }
    }

    // Test API connection
    async testConnection() {
        try {
            const response = await fetch(`${ChatUI.API_BASE_URL}/test_endpoint.php`);
            if (!response.ok) {
                throw new Error('API connection failed');
            }
            if (this.connectionStatus) {
                this.connectionStatus.textContent = 'Connected';
                this.connectionStatus.className = 'badge text-bg-success';
            }
        } catch (error) {
            console.error('Connection test failed:', error);
            if (this.connectionStatus) {
                this.connectionStatus.textContent = 'Disconnected';
                this.connectionStatus.className = 'badge text-bg-danger';
            }
            throw error;
        }
    }

    // Send message to server
    async sendMessage() {
        const message = this.messageInput?.value.trim();
        if (!message) {
            console.log('No message to send');
            return;
        }

        // Clear input and reset height
        this.messageInput.value = '';
        this.messageInput.style.height = 'auto';

        // Add user message to chat
        this.addMessage(message, 'user');

        // Show typing indicator
        this.showTypingIndicator();

        try {
            const token = localStorage.getItem('token');
            if (!token) {
                throw new Error('No authentication token found');
            }

            console.log('Sending message:', message);
            const response = await fetch(`${ChatUI.API_BASE_URL}/chat.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify({ message })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            console.log('Server response:', data);

            if (data.error) {
                throw new Error(data.error);
            }

            // Hide typing indicator and add assistant's response
            this.hideTypingIndicator();
            this.addMessage(data.response, 'assistant');

            // Clear any previous errors
            this.hideError();

        } catch (error) {
            console.error('Error in sendMessage:', error);
            this.hideTypingIndicator();
            this.showError(error.message);

            // If token is invalid, redirect to login
            if (error.message.includes('token') || error.message.includes('authentication')) {
                localStorage.removeItem('token');
                window.location.href = 'login.html';
            }
        }

        // Scroll to bottom
        this.scrollToBottom();
    }

    // Add message to chat
    addMessage(content, role) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${role}`;
        messageDiv.textContent = content;
        this.messageContainer?.appendChild(messageDiv);
        this.scrollToBottom();
    }

    // Show typing indicator
    showTypingIndicator() {
        this.typingIndicator?.classList.remove('d-none');
        this.scrollToBottom();
    }

    // Hide typing indicator
    hideTypingIndicator() {
        this.typingIndicator?.classList.add('d-none');
    }

    // Show error message
    showError(message) {
        if (this.errorMessage) {
            this.errorMessage.textContent = message;
            this.errorMessage.classList.remove('d-none');
        }
    }

    // Hide error message
    hideError() {
        this.errorMessage?.classList.add('d-none');
    }

    // Adjust textarea height
    adjustTextareaHeight() {
        if (this.messageInput) {
            this.messageInput.style.height = 'auto';
            this.messageInput.style.height = `${this.messageInput.scrollHeight}px`;
        }
    }

    // Scroll chat to bottom
    scrollToBottom() {
        if (this.messageContainer) {
            this.messageContainer.scrollTop = this.messageContainer.scrollHeight;
        }
    }
}

// Initialize chat UI when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new ChatUI();
});
