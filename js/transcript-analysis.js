// Transcript Analysis Visualization
class TranscriptAnalysis {
    static async importContent() {
        try {
            const content = document.getElementById('contentInput').value.trim();
            if (!content) {
                alert('Please enter some content to import');
                return;
            }

            const token = localStorage.getItem('token');
            if (!token) {
                throw new Error('No authentication token found');
            }

            const response = await fetch(`${TranscriptAnalysis.API_BASE_URL}/import-transcripts.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify({ content })
            });

            if (!response.ok) {
                throw new Error('Failed to import content');
            }

            const result = await response.json();
            if (result.status === 'success') {
                alert('Content imported successfully!');
                document.getElementById('contentInput').value = '';
                // Refresh the analysis
                new TranscriptAnalysis();
            } else {
                throw new Error(result.error || 'Failed to import content');
            }
        } catch (error) {
            console.error('Error:', error);
            alert(error.message);
        }
    }


    static API_BASE_URL = 'http://localhost:8888/ai_analytics_dashboard/api';
    
    constructor() {
        this.charts = {};
        this.initializeCharts();
    }

    async initializeCharts() {
        try {
            const token = localStorage.getItem('token');
            if (!token) {
                throw new Error('No authentication token found');
            }

            const response = await fetch(`${this.API_BASE_URL}/transcript-analysis.php`, {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });

            if (!response.ok) {
                throw new Error('Failed to fetch analysis data');
            }

            const data = await response.json();
            if (data.status === 'success') {
                this.renderCharts(data.data);
                this.renderDiscussions(data.data.discussions);
            } else {
                throw new Error(data.error || 'Failed to analyze transcripts');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showError(error.message);
        }
    }

    renderCharts(data) {
        // Topic Distribution Chart
        const topicCtx = document.getElementById('topicDistributionChart').getContext('2d');
        this.charts.topicChart = new Chart(topicCtx, {
            type: 'pie',
            data: data.chartData,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right',
                    },
                    title: {
                        display: true,
                        text: 'Topic Distribution'
                    }
                }
            }
        });

        // Sentiment Analysis Chart
        const sentimentCtx = document.getElementById('sentimentAnalysisChart').getContext('2d');
        this.charts.sentimentChart = new Chart(sentimentCtx, {
            type: 'bar',
            data: data.sentimentChartData,
            options: {
                responsive: true,
                scales: {
                    x: {
                        stacked: true,
                    },
                    y: {
                        stacked: true
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Sentiment Analysis by Topic'
                    }
                }
            }
        });
    }

    renderDiscussions(discussions) {
        const container = document.getElementById('discussionSamples');
        container.innerHTML = '';

        for (const [topic, samples] of Object.entries(discussions)) {
            const topicSection = document.createElement('div');
            topicSection.className = 'mb-4';
            topicSection.innerHTML = `
                <h4 class="mb-3">${topic}</h4>
                <div class="list-group">
                    ${samples.map(sample => `
                        <div class="list-group-item">
                            <p class="mb-1">${sample}</p>
                        </div>
                    `).join('')}
                </div>
            `;
            container.appendChild(topicSection);
        }
    }

    showError(message) {
        const container = document.getElementById('analysisContainer');
        container.innerHTML = `
            <div class="alert alert-danger" role="alert">
                ${message}
            </div>
        `;
    }
}

// Initialize when document is ready
document.addEventListener('DOMContentLoaded', () => {
    new TranscriptAnalysis();
});
