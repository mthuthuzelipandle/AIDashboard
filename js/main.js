document.addEventListener('DOMContentLoaded', () => {
    // Check for existing token and auto-login
    const token = localStorage.getItem('token');
    if (token) {
        showDashboard();
    }
    // Initialize navigation
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const pageId = e.target.closest('.nav-link').dataset.page;
            showPage(pageId);
        });
    });

    // Initialize logout
    document.getElementById('logout')?.addEventListener('click', (e) => {
        e.preventDefault();
        logout();
    });

    // Initialize charts
    const charts = {
        sentiment: null,
        trends: null,
        gender: null,
        age: null,
        location: null
    };

    // Show error message in the login form
    function showLoginError(message) {
        const errorDiv = document.getElementById('login-error');
        if (errorDiv) {
            errorDiv.textContent = message;
            errorDiv.classList.remove('d-none');
        }
    }

    // Clear login error
    function clearLoginError() {
        const errorDiv = document.getElementById('login-error');
        if (errorDiv) {
            errorDiv.classList.add('d-none');
            errorDiv.textContent = '';
        }
    }

    // Authentication handling
    const loginForm = document.getElementById('login-form');
    const submitButton = loginForm.querySelector('button[type="submit"]');
    
    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        clearLoginError();

        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;

        // Basic validation
        if (!email || !email.match(/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$/i)) {
            showLoginError('Please enter a valid email address');
            return;
        }

        if (!password || password.length < 6) {
            showLoginError('Password must be at least 6 characters');
            return;
        }

        // Show loading state
        submitButton.disabled = true;
        const spinner = submitButton.querySelector('.spinner-border');
        const buttonText = submitButton.querySelector('span:not(.spinner-border)');
        spinner.classList.remove('d-none');
        buttonText.textContent = 'Signing in...';

        // Add loading state to form
        const inputs = loginForm.querySelectorAll('input');
        inputs.forEach(input => input.disabled = true);

        try {
            const response = await fetch('api/auth.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ email, password })
            });

            const data = await response.json();

            if (response.ok) {
                localStorage.setItem('token', data.token);
                showDashboard();
                loadUserStats();
            } else {
                showLoginError(data.error || 'Invalid email or password');
            }
        } catch (error) {
            showLoginError('Network error. Please try again.');
        } finally {
            // Reset form state
            submitButton.disabled = false;
            const spinner = submitButton.querySelector('.spinner-border');
            const buttonText = submitButton.querySelector('span:not(.spinner-border)');
            spinner.classList.add('d-none');
            buttonText.textContent = 'Sign in';

            // Re-enable inputs
            const inputs = loginForm.querySelectorAll('input');
            inputs.forEach(input => input.disabled = false);
        }
    });

    // Navigation handling
    document.querySelectorAll('[data-page], .nav-link').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const target = e.target.closest('.nav-link');
            
            if (!target) return;
            
            if (target.id === 'logout') {
                logout();
                return;
            }

            const pageId = target.dataset.page;
            if (pageId) {
                showPage(pageId);
                
                // Update active state
                document.querySelectorAll('.nav-link').forEach(navLink => {
                    navLink.classList.remove('active');
                });
                target.classList.add('active');
            }
        });
    });

    // Helper functions
    function showDashboard() {
        document.getElementById('login-container').classList.add('d-none');
        document.getElementById('dashboard-container').classList.remove('d-none');
        
        // Show analytics hub by default
        showPage('analytics-hub');
        
        // Update navigation state
        document.querySelector('[data-page="analytics-hub"]')?.classList.add('active');
    }

    function showPage(pageId) {
        // Hide all pages
        document.querySelectorAll('.dashboard-page').forEach(page => {
            page.classList.add('d-none');
        });

        // Show target page
        const targetPage = document.getElementById(`${pageId}-page`);
        if (!targetPage) {
            console.error(`Page element ${pageId}-page not found!`);
            return;
        }
        targetPage.classList.remove('d-none');
        
        // Load page-specific data
        if (pageId === 'analytics') {
            loadAnalytics();
        } else if (pageId === 'user-stats') {
            loadUserStats();
        } else if (pageId === 'sample-charts') {
            // Sample charts are automatically initialized
            // when sample-charts.js loads
        }
    }

    function logout() {
        // Clear all auth data
        localStorage.removeItem('token');
        
        // Reset UI state
        document.getElementById('dashboard-container').classList.add('d-none');
        document.getElementById('login-container').classList.remove('d-none');
        
        // Clear any sensitive data from memory
        Object.keys(charts).forEach(key => {
            if (charts[key]) {
                charts[key].destroy();
                charts[key] = null;
            }
        });
        
        // Clear any error messages
        clearLoginError();
        
        // Reset form
        loginForm.reset();
    }



    // Chat functionality is now handled by chat.js

    async function loadAnalytics() {
        console.log('Loading analytics data...');
        const token = localStorage.getItem('token');
        console.log('Using token:', token ? token.substring(0, 10) + '...' : 'No token found');
        try {
            const response = await fetch('api/analytics.php', {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                }
            });

            if (response.ok) {
                const data = await response.json();
                console.log('Analytics data received:', data);
                if (!data.error) {
                    updateCharts(data);
                } else {
                    console.error('Server error:', data.error);
                    if (data.error === 'Invalid token' || data.error === 'No token provided') {
                        localStorage.removeItem('token');
                        showPage('login');
                    }
                }
            }
        } catch (error) {
            console.error('Analytics error:', error);
        }
    }

    async function loadUserStats() {
        try {
            const response = await fetch('api/user-stats.php', {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                }
            });

            if (response.ok) {
                const data = await response.json();
                updateUserStats(data);
            }
        } catch (error) {
            console.error('User stats error:', error);
        }
    }

    function updateCharts(data) {
        console.log('Updating charts with data:', data);
        
        // Get chart contexts
        const sentimentCtx = document.getElementById('sentimentChart');
        const trendsCtx = document.getElementById('trendsChart');
        
        if (!sentimentCtx || !trendsCtx) {
            console.error('Chart canvas elements not found!');
            return;
        }

        console.log('Chart.js version:', Chart.version);
        console.log('Chart contexts found:', { sentimentCtx, trendsCtx });
        try {
            // Update sentiment analysis chart
            if (charts.sentiment) {
                console.log('Destroying existing sentiment chart');
                charts.sentiment.destroy();
            }
            console.log('Creating new sentiment chart');
            charts.sentiment = new Chart(sentimentCtx, {
                type: 'pie',
                data: {
                    labels: ['Positive', 'Neutral', 'Negative'],
                    datasets: [{
                        data: data.sentiment,
                        backgroundColor: ['#28a745', '#ffc107', '#dc3545']
                    }]
                }
            });
        } catch (error) {
            console.error('Error creating sentiment chart:', error);
        }

        try {
            // Update trends chart
            if (charts.trends) {
                console.log('Destroying existing trends chart');
                charts.trends.destroy();
            }
            console.log('Creating new trends chart');
            charts.trends = new Chart(trendsCtx, {
                type: 'line',
                data: {
                    labels: data.trends.labels,
                    datasets: [{
                        label: 'Trend Analysis',
                        data: data.trends.values,
                        borderColor: '#007bff'
                    }]
                }
            });
        } catch (error) {
            console.error('Error creating trends chart:', error);
        }
    }

    function updateUserStats(data) {
        // Update gender distribution chart
        if (charts.gender) charts.gender.destroy();
        charts.gender = new Chart(document.getElementById('genderChart'), {
            type: 'doughnut',
            data: {
                labels: Object.keys(data.gender),
                datasets: [{
                    data: Object.values(data.gender),
                    backgroundColor: ['#007bff', '#ff69b4', '#6c757d']
                }]
            }
        });

        // Update age distribution chart
        if (charts.age) charts.age.destroy();
        charts.age = new Chart(document.getElementById('ageChart'), {
            type: 'bar',
            data: {
                labels: Object.keys(data.age),
                datasets: [{
                    label: 'Age Distribution',
                    data: Object.values(data.age),
                    backgroundColor: '#28a745'
                }]
            }
        });

        // Update location distribution chart
        if (charts.location) charts.location.destroy();
        charts.location = new Chart(document.getElementById('locationChart'), {
            type: 'pie',
            data: {
                labels: Object.keys(data.location),
                datasets: [{
                    data: Object.values(data.location),
                    backgroundColor: [
                        '#007bff', '#28a745', '#dc3545', 
                        '#ffc107', '#17a2b8', '#6c757d'
                    ]
                }]
            }
        });
    }
});
