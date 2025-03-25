// Debug mode
const DEBUG = true;

// Logging function
function log(...args) {
    if (DEBUG) console.log('[Analytics]', ...args);
}

// Verify Chart.js is loaded
if (!window.Chart) {
    console.error('Chart.js not loaded!');
    throw new Error('Chart.js is required but not loaded');
}

log('Chart.js version:', Chart.version);

// Global state
const chartInstances = {};
let originalData = null;

// API base URL
const API_BASE_URL = '/ai_analytics_dashboard/api';

// Chart colors
const chartColors = [
    'rgba(59, 130, 246, 0.8)',   // Blue
    'rgba(16, 185, 129, 0.8)',    // Green
    'rgba(245, 158, 11, 0.8)',    // Yellow
    'rgba(239, 68, 68, 0.8)',     // Red
    'rgba(139, 92, 246, 0.8)'     // Purple
];

// Chart hover colors (slightly darker)
const hoverColors = chartColors.map(color => color.replace('0.8', '1'));

// Chart options
const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    animation: {
        duration: 1000,
        easing: 'easeOutQuart'
    },
    plugins: {
        legend: {
            position: 'bottom',
            labels: {
                padding: 20,
                usePointStyle: true,
                font: { size: 12 }
            }
        },
        datalabels: {
            color: '#fff',
            font: { weight: 'bold' },
            formatter: (value, ctx) => {
                const sum = ctx.dataset.data.reduce((a, b) => a + b, 0);
                const percentage = ((value / sum) * 100).toFixed(1);
                return percentage > 5 ? percentage + '%' : '';
            }
        },
        tooltip: {
            backgroundColor: 'rgba(0, 0, 0, 0.8)',
            titleFont: { size: 14 },
            bodyFont: { size: 13 },
            padding: 12,
            callbacks: {
                label: (context) => {
                    const value = context.raw;
                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                    const percentage = ((value / total) * 100).toFixed(1);
                    return ` Count: ${value} (${percentage}%)`;
                }
            }
        }
    }
};

// Check authentication and redirect if needed
async function checkAuth() {
    const token = localStorage.getItem('token');
    if (!token) {
        window.location.href = '/ai_analytics_dashboard/login.html';
        return false;
    }
    return true;
}

// Create charts
function createCharts(data) {
    log('Starting createCharts');
    if (!data || !data.genderDistribution || !data.ageDistribution || !data.locationDistribution) {
        console.error('Invalid data structure:', data);
        return;
    }
    log('Data validation passed');

    // Verify Chart.js is loaded
    if (typeof Chart === 'undefined') {
        console.error('Chart.js is not loaded!');
        return;
    }
    log('Chart.js is loaded:', Chart.version);

    // Verify DataLabels plugin is registered
    if (!Chart.registry.plugins.get('datalabels')) {
        console.error('DataLabels plugin is not registered!');
        return;
    }
    log('DataLabels plugin is registered');

    // Verify chart containers exist
    const containers = ['genderChart', 'ageChart', 'locationChart'];
    for (const id of containers) {
        const container = document.getElementById(id);
        if (!container) {
            console.error(`Chart container #${id} not found`);
            return;
        }
        log(`Found chart container #${id}`);
    }

    // Destroy existing charts
    Object.values(chartInstances).forEach(chart => {
        if (chart) {
            log('Destroying existing chart instance');
            chart.destroy();
        }
    });

    // Gender Distribution Chart
    const genderCtx = document.getElementById('genderChart');
    if (genderCtx) {
        log('Creating gender chart');
        const genderData = data.genderDistribution;
        chartInstances.gender = new Chart(genderCtx, {
            type: 'doughnut',
            data: {
                labels: genderData.map(item => item.gender),
                datasets: [{
                    data: genderData.map(item => item.count),
                    backgroundColor: chartColors,
                    hoverBackgroundColor: hoverColors,
                    borderWidth: 0,
                    borderRadius: 4
                }]
            },
            options: {
                ...chartOptions,
                plugins: {
                    ...chartOptions.plugins,
                    title: {
                        display: true,
                        text: 'Gender Distribution',
                        font: { size: 16, weight: 'bold' },
                        padding: { bottom: 20 }
                    }
                }
            }
        });
    }

    // Age Distribution Chart
    const ageCtx = document.getElementById('ageChart');
    if (ageCtx) {
        log('Creating age chart');
        const ageData = data.ageDistribution;
        chartInstances.age = new Chart(ageCtx, {
            type: 'bar',
            data: {
                labels: ageData.map(item => item.age_group),
                datasets: [{
                    data: ageData.map(item => item.count),
                    backgroundColor: chartColors[1],
                    borderWidth: 0,
                    borderRadius: 4,
                    maxBarThickness: 50
                }]
            },
            options: {
                ...chartOptions,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            display: true,
                            drawBorder: false,
                            color: 'rgba(0, 0, 0, 0.1)'
                        },
                        ticks: {
                            stepSize: 1,
                            font: { size: 12 }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: { size: 12 }
                        }
                    }
                },
                plugins: {
                    ...chartOptions.plugins,
                    title: {
                        display: true,
                        text: 'Age Distribution',
                        font: { size: 16, weight: 'bold' },
                        padding: { bottom: 20 }
                    }
                }
            }
        });
    }

    // Location Distribution Chart
    const locationCtx = document.getElementById('locationChart');
    if (locationCtx) {
        log('Creating location chart');
        const locationData = data.locationDistribution;
        chartInstances.location = new Chart(locationCtx, {
            type: 'bar',
            data: {
                labels: locationData.map(item => item.location),
                datasets: [{
                    data: locationData.map(item => item.count),
                    backgroundColor: chartColors[2],
                    borderWidth: 0,
                    borderRadius: 4,
                    maxBarThickness: 30
                }]
            },
            options: {
                ...chartOptions,
                indexAxis: 'y',
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: {
                            display: true,
                            drawBorder: false,
                            color: 'rgba(0, 0, 0, 0.1)'
                        },
                        ticks: {
                            stepSize: 1,
                            font: { size: 12 }
                        }
                    },
                    y: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: { size: 12 }
                        }
                    }
                },
                plugins: {
                    ...chartOptions.plugins,
                    title: {
                        display: true,
                        text: 'Location Distribution',
                        font: { size: 16, weight: 'bold' },
                        padding: { bottom: 20 }
                    }
                }
            }
        });
    }
}

// Fetch analytics data
async function fetchAnalytics() {
    log('Starting fetchAnalytics');
    log('API base URL:', API_BASE_URL);
    try {
        if (!await checkAuth()) return;

        const token = localStorage.getItem('token');
        log('Token from localStorage:', token ? token.substring(0, 20) + '...' : 'No token');

        const response = await fetch(`${API_BASE_URL}/analytics.php`, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });

        log('API Response status:', response.status);

        if (response.status === 401) {
            window.location.href = '/ai_analytics_dashboard/login.html';
            return;
        }

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        log('Received analytics data:', data);
        if (data.error) {
            console.error('API error:', data.error);
            return;
        }
        if (!data.genderDistribution || !data.ageDistribution || !data.locationDistribution) {
            console.error('Missing required data properties:', data);
            return;
        }
        originalData = data;
        log('Creating charts with data:', data);
        try {
            createCharts(data);
        } catch (chartError) {
            console.error('Error creating charts:', chartError);
        }
    } catch (error) {
        console.error('Error fetching analytics:', error);
    }
}

// Initialize dashboard
document.addEventListener('DOMContentLoaded', fetchAnalytics);
