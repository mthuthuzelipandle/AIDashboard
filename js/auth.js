// Check if user is already logged in
function checkAuth() {
    const token = localStorage.getItem('token');
    if (!token) {
        console.log('No token found');
        showLogin();
        return;
    }

    // Validate token
    console.log('Validating token...');
    fetch('http://localhost:8888/ai_analytics_dashboard/api/verify_token.php', {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'Authorization': `Bearer ${token}`
        }
    })
    .then(async response => {
        if (!response.ok) {
            throw new Error('Token validation failed');
        }
        
        const data = await response.json();
        if (data.status === 'success') {
            console.log('Token validated successfully');
            showDashboard();
        } else {
            throw new Error(data.error || 'Token validation failed');
        }
    })
    .catch(error => {
        console.error('Auth check failed:', error);
        localStorage.removeItem('token');
        showLogin();
    });
}

// Show login container
function showLogin() {
    document.getElementById('login-container').classList.remove('d-none');
    document.getElementById('dashboard-container').classList.add('d-none');
    localStorage.removeItem('token');
}

// Show dashboard container
function showDashboard() {
    console.log('Showing dashboard...');
    document.getElementById('login-container').classList.add('d-none');
    document.getElementById('dashboard-container').classList.remove('d-none');
    
    // Initialize dashboard only if not already initialized
    if (typeof window.dashboardInitialized === 'undefined' || !window.dashboardInitialized) {
        console.log('Initializing dashboard...');
        if (typeof initializeDashboard === 'function') {
            initializeDashboard();
            window.dashboardInitialized = true;
        } else {
            console.warn('initializeDashboard function not found');
        }
    } else {
        console.log('Dashboard already initialized');
    }
}

// Handle login form submission
document.getElementById('login-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const errorDiv = document.getElementById('login-error');
    const submitButton = this.querySelector('button[type="submit"]');
    const spinner = submitButton.querySelector('.spinner-border');
    
    // Show loading state
    submitButton.disabled = true;
    spinner.classList.remove('d-none');
    errorDiv.classList.add('d-none');
    
    try {
        // Validate form data
        if (!email || !password) {
            throw new Error('Email and password are required');
        }

        // Make login request
        const response = await fetch('http://localhost:8888/ai_analytics_dashboard/api/auth.php', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                email: email.trim(),
                password: password.trim()
            })
        });

        // Handle non-JSON responses
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Invalid server response');
        }

        const data = await response.json();
        
        if (response.ok) {
            // Store token and show dashboard
            localStorage.setItem('token', data.token);
            showDashboard();
        } else {
            throw new Error(data.error || 'Login failed');
        }
    } catch (error) {
        console.error('Login error:', error);
        errorDiv.textContent = error.message;
        errorDiv.classList.remove('d-none');
    } finally {
        // Reset loading state
        submitButton.disabled = false;
        spinner.classList.add('d-none');
    }
});

// Handle logout
function logout() {
    localStorage.removeItem('token');
    showLogin();
}

// Initialize auth check
document.addEventListener('DOMContentLoaded', checkAuth);
