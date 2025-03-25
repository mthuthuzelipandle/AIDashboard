// Chart colors and setup
const colors = {
    primary: 'rgba(59, 130, 246, 0.8)',   // Blue
    success: 'rgba(16, 185, 129, 0.8)',    // Green
    warning: 'rgba(245, 158, 11, 0.8)',    // Yellow
    danger: 'rgba(239, 68, 68, 0.8)',      // Red
    purple: 'rgba(139, 92, 246, 0.8)',     // Purple
    cyan: 'rgba(6, 182, 212, 0.8)',        // Cyan
};

const colorArray = Object.values(colors);

// Chart dimensions
const CHART_WIDTH = 400;
const CHART_HEIGHT = 300;

// Common chart options
const commonOptions = {
    responsive: false,
    maintainAspectRatio: false,
    animation: {
        duration: 0 // Disable animations for now
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
            font: { weight: 'bold' }
        }
    }
};

// Function to prepare canvas
function prepareCanvas(canvasId) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return null;
    
    canvas.width = CHART_WIDTH;
    canvas.height = CHART_HEIGHT;
    canvas.style.width = CHART_WIDTH + 'px';
    canvas.style.height = CHART_HEIGHT + 'px';
    
    return canvas;
}

// Chart instances
let pieChart, barChart, lineChart, doughnutChart, radarChart, polarChart;

// Initialize all charts
function initializeCharts() {
    console.log('Initializing charts...');

    // Destroy existing charts
    [pieChart, barChart, lineChart, doughnutChart, radarChart, polarChart].forEach(chart => {
        if (chart) chart.destroy();
    });

    try {
        // Prepare canvases
        const pieCanvas = prepareCanvas('pieChart');
        const barCanvas = prepareCanvas('barChart');
        const lineCanvas = prepareCanvas('lineChart');
        const doughnutCanvas = prepareCanvas('doughnutChart');
        const radarCanvas = prepareCanvas('radarChart');
        const polarCanvas = prepareCanvas('polarChart');

        if (!pieCanvas || !barCanvas || !lineCanvas || !doughnutCanvas || !radarCanvas || !polarCanvas) {
            console.error('Some canvases not found');
            return;
        }
            // Create pie chart
            pieChart = new Chart(pieCanvas, {
    type: 'pie',
    data: {
        labels: ['18-24', '25-34', '35-44', '45-54', '55+'],
        datasets: [{
            data: [15, 30, 25, 18, 12],
            backgroundColor: colorArray,
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
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
                formatter: (value) => value + '%'
            }
        }
    }
});

            // Create bar chart
            barChart = new Chart(barCanvas, {
    type: 'bar',
    data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
        datasets: [{
            label: 'Sales',
            data: [65, 59, 80, 81, 56, 90],
            backgroundColor: colors.primary,
            borderWidth: 0
        }]
    },
    options: {
        ...commonOptions,
        plugins: {
            ...commonOptions.plugins,
            datalabels: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    display: false
                }
            }
        }
    }
});

            // Create line chart
            lineChart = new Chart(lineCanvas, {
    type: 'line',
    data: {
        labels: ['Q1', 'Q2', 'Q3', 'Q4'],
        datasets: [{
            label: '2024',
            data: [120, 150, 180, 220],
            borderColor: colors.success,
            tension: 0.4,
            fill: false
        }, {
            label: '2025',
            data: [150, 190, 220, 280],
            borderColor: colors.primary,
            tension: 0.4,
            fill: false
        }]
    },
    options: {
        ...commonOptions,
        plugins: {
            ...commonOptions.plugins,
            datalabels: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    display: false
                }
            }
        }
    }
});

            // Create doughnut chart
            doughnutChart = new Chart(doughnutCanvas, {
    type: 'doughnut',
    data: {
        labels: ['Electronics', 'Clothing', 'Food', 'Books', 'Other'],
        datasets: [{
            data: [30, 25, 20, 15, 10],
            backgroundColor: colorArray,
            borderWidth: 0
        }]
    },
    options: {
        ...commonOptions,
        plugins: {
            ...commonOptions.plugins,
            datalabels: {
                formatter: (value) => value + '%'
            }
        },
        cutout: '60%'
    }
});

            // Create radar chart
            radarChart = new Chart(radarCanvas, {
    type: 'radar',
    data: {
        labels: ['Technical', 'Communication', 'Teamwork', 'Leadership', 'Problem Solving'],
        datasets: [{
            label: 'Current',
            data: [85, 75, 90, 80, 85],
            backgroundColor: colors.primary + '40',
            borderColor: colors.primary,
            borderWidth: 2,
            pointBackgroundColor: colors.primary
        }, {
            label: 'Target',
            data: [90, 85, 95, 90, 90],
            backgroundColor: colors.success + '40',
            borderColor: colors.success,
            borderWidth: 2,
            pointBackgroundColor: colors.success
        }]
    },
    options: {
        ...commonOptions,
        plugins: {
            ...commonOptions.plugins,
            datalabels: {
                display: false
            }
        },
        scales: {
            r: {
                min: 0,
                max: 100,
                ticks: {
                    stepSize: 20
                }
            }
        }
    }
});

            // Create polar area chart
            polarChart = new Chart(polarCanvas, {
    type: 'polarArea',
    data: {
        labels: ['Development', 'Marketing', 'Sales', 'Support', 'Research'],
        datasets: [{
            data: [300, 250, 200, 150, 100],
            backgroundColor: colorArray,
            borderWidth: 0
        }]
    },
    options: {
        ...commonOptions,
        plugins: {
            ...commonOptions.plugins,
            datalabels: {
                formatter: (value, ctx) => {
                    const sum = ctx.dataset.data.reduce((a, b) => a + b, 0);
                    return Math.round((value / sum) * 100) + '%';
                }
            }
        }
    }
});

            // Create word cloud
            const wordCloudContainer = document.getElementById('wordCloud');
            if (!wordCloudContainer) {
                console.error('Word cloud container not found');
                return;
            }

            // Clear any existing content
            wordCloudContainer.innerHTML = '';

            // Get container dimensions
            const containerRect = wordCloudContainer.getBoundingClientRect();
            const width = containerRect.width;
            const height = containerRect.height;

            // Create SVG element with responsive dimensions
            const svg = d3.select(wordCloudContainer)
                .append('svg')
                .attr('width', '100%')
                .attr('height', '100%')
                .attr('viewBox', `0 0 ${width} ${height}`)
                .attr('preserveAspectRatio', 'xMidYMid meet');

            // Sample words with varying sizes
            const words = [
                { text: 'AI', size: 40 },
                { text: 'Analytics', size: 35 },
                { text: 'Data', size: 30 },
                { text: 'Machine Learning', size: 25 },
                { text: 'Cloud', size: 35 },
                { text: 'Python', size: 30 },
                { text: 'JavaScript', size: 25 },
                { text: 'SQL', size: 30 },
                { text: 'NoSQL', size: 25 },
                { text: 'API', size: 20 },
                { text: 'Visualization', size: 28 },
                { text: 'Dashboard', size: 32 },
                { text: 'Charts', size: 27 },
                { text: 'Insights', size: 33 }
            ];

            // Create word cloud layout
            const layout = d3.layout.cloud()
                .size([width, height])
                .words(words)
                .padding(5)
                .rotate(() => (~~(Math.random() * 2) * 90))
                .font('Impact')
                .fontSize(d => d.size)
                .on('end', words => {
                    // Center group
                    const g = svg.append('g')
                        .attr('transform', `translate(${width/2},${height/2})`);

                    // Add words
                    g.selectAll('text')
                        .data(words)
                        .enter()
                        .append('text')
                        .style('font-size', d => `${d.size}px`)
                        .style('font-family', 'Impact')
                        .style('fill', (d, i) => colorArray[i % colorArray.length])
                        .style('cursor', 'pointer')
                        .attr('text-anchor', 'middle')
                        .attr('transform', d => `translate(${d.x},${d.y})rotate(${d.rotate})`)
                        .text(d => d.text)
                        .on('mouseover', function() {
                            d3.select(this)
                                .transition()
                                .duration(200)
                                .style('opacity', 0.7)
                                .style('font-size', d => `${d.size * 1.2}px`);
                        })
                        .on('mouseout', function() {
                            d3.select(this)
                                .transition()
                                .duration(200)
                                .style('opacity', 1)
                                .style('font-size', d => `${d.size}px`);
                        });
                });

            layout.start();
        } catch (error) {
            console.error('Error initializing charts:', error);
        }
}

// Handle window resize
function handleResize() {
    requestAnimationFrame(() => {
        // Resize Chart.js charts
        const charts = [pieChart, barChart, lineChart, doughnutChart, radarChart, polarChart];
        charts.forEach(chart => {
            if (chart && chart.canvas) {
                resizeChart(chart.canvas);
                chart.resize();
            }
        });

        // Reinitialize word cloud for better responsiveness
        const wordCloudContainer = document.getElementById('wordCloud');
        if (wordCloudContainer && !wordCloudContainer.classList.contains('resizing')) {
            wordCloudContainer.classList.add('resizing');
            setTimeout(() => {
                wordCloudContainer.classList.remove('resizing');
                if (!document.getElementById('sample-charts-page').classList.contains('d-none')) {
                    initializeCharts();
                }
            }, 250);
        }
    });
}

// Initialize charts when navigation occurs
document.addEventListener('DOMContentLoaded', () => {
    // Watch for navigation to sample charts page
    document.querySelectorAll('[data-page="sample-charts"]').forEach(link => {
        link.addEventListener('click', () => {
            console.log('Sample charts navigation clicked');
            setTimeout(initializeCharts, 300); // Longer delay to ensure page transition
        });
    });
});

// Initialize on page load if visible
window.addEventListener('load', () => {
    const sampleChartsPage = document.getElementById('sample-charts-page');
    if (sampleChartsPage && !sampleChartsPage.classList.contains('d-none')) {
        setTimeout(initializeCharts, 300);
    }
});

// Watch for page visibility changes
const observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
        if (mutation.target.id === 'sample-charts-page' && 
            mutation.attributeName === 'class' && 
            !mutation.target.classList.contains('d-none')) {
            setTimeout(initializeCharts, 300);
        }
    });
});

// Start observing once DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    const sampleChartsPage = document.getElementById('sample-charts-page');
    if (sampleChartsPage) {
        observer.observe(sampleChartsPage, { attributes: true });
    }
});
