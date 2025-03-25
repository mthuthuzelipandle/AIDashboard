// Analytics Hub functionality
document.addEventListener('DOMContentLoaded', function() {
    // API configuration
    const API_BASE_URL = 'http://localhost:8888/ai_analytics_dashboard/api';
    const USER_STATS_URL = `${API_BASE_URL}/user-stats.php`;
    const QUICK_STATS_URL = `${API_BASE_URL}/quick-stats.php`;
    const RECENT_ANALYTICS_URL = `${API_BASE_URL}/recent-analytics.php`;
    const SAVE_ANALYTIC_URL = `${API_BASE_URL}/save-analytic.php`;

    // Export modal setup
    const exportModal = document.getElementById('export-modal');
    const exportForm = document.getElementById('export-form');
    const exportTypeSelect = document.getElementById('export-type');
    const exportFormatSelect = document.getElementById('export-format');
    const dateRangeStart = document.getElementById('date-range-start');
    const dateRangeEnd = document.getElementById('date-range-end');
    const chartTypeSelect = document.getElementById('chart-type');
    const chartThemeSelect = document.getElementById('chart-theme');
    const exportTypeRadios = document.querySelectorAll('[name="export-type"]');
    
    // Initialize Bootstrap modal if element exists
    const bsExportModal = exportModal ? new bootstrap.Modal(exportModal) : null;
    
    // Initialize date pickers if they exist
    if (dateRangeStart && dateRangeEnd) {
        const today = new Date();
        const thirtyDaysAgo = new Date(today);
        thirtyDaysAgo.setDate(today.getDate() - 30);
        
        dateRangeStart.value = thirtyDaysAgo.toISOString().split('T')[0];
        dateRangeEnd.value = today.toISOString().split('T')[0];
    }

    // Show toast notification
    function showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        
        const container = document.getElementById('toast-container');
        if (container) {
            container.appendChild(toast);
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();
            
            toast.addEventListener('hidden.bs.toast', () => {
                toast.remove();
            });
        }
    }

    // View toggle handlers
    const viewButtons = document.querySelectorAll('[data-view]');
    viewButtons.forEach(button => {
        button.addEventListener('click', function() {
            viewButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            updateViewMode(this.dataset.view);
        });
    });

    // Error handling helper
    function handleApiError(error, context) {
        console.error(`Error ${context}:`, error);
        if (error.response) {
            console.error('Response:', error.response);
        }
    }

    // Quick stats update
    function updateQuickStats() {
        fetch(QUICK_STATS_URL, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`,
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.activeUsers !== undefined) {
                document.getElementById('active-users-count').textContent = data.activeUsers;
                document.getElementById('growth-rate').textContent = `${data.growthRate}%`;
                document.getElementById('sentiment-score').textContent = data.sentimentScore.toFixed(1);
                document.getElementById('avg-session').textContent = `${data.avgSession}m`;
            } else {
                console.warn('Unexpected data format:', data);
            }
        })
        .catch(error => {
            console.error('Error fetching quick stats:', error);
            handleApiError(error, 'fetching quick stats');
        });
    }

    // Recent analytics table
    function loadRecentAnalytics() {
        fetch(RECENT_ANALYTICS_URL, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`,
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            const tbody = document.querySelector('#recent-analytics-table tbody');
            if (!tbody) {
                console.warn('Analytics table body not found');
                return;
            }
            tbody.innerHTML = '';
            
            if (!Array.isArray(data) || data.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="4" class="text-center text-muted">
                            <div class="py-4">
                                <i class="bi bi-clipboard-data fs-2 d-block mb-2"></i>
                                <p class="mb-0">No analytics data available yet</p>
                            </div>
                        </td>
                    </tr>
                `;
                return;
            }
            
            data.forEach(item => {
                const tr = document.createElement('tr');
                const type = item.type || 'unknown';
                const typeColor = getTypeColor(type);
                
                tr.innerHTML = `
                    <td>
                        <div class="d-flex align-items-center">
                            <span class="badge bg-${typeColor} bg-opacity-10 text-${typeColor} rounded-pill px-3 py-2">
                                ${type}
                            </span>
                        </div>
                    </td>
                    <td>${item.description || 'No description'}</td>
                    <td>${formatDate(item.created_at)}</td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-light" onclick="viewAnalytic('${item.id}')" title="View">
                                <i class="bi bi-eye"></i>
                            </button>
                            <button class="btn btn-light" onclick="shareAnalytic('${item.id}')" title="Share">
                                <i class="bi bi-share"></i>
                            </button>
                            <button class="btn btn-light" onclick="saveAnalytic('${item.id}')" title="Save">
                                <i class="bi bi-bookmark"></i>
                            </button>
                        </div>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        })
        .catch(error => {
            console.error('Error loading recent analytics:', error);
            handleApiError(error, 'loading recent analytics');
        });
    }

    // Helper functions
    function getTypeColor(type) {
        const colors = {
            'overview': 'primary',
            'user': 'success',
            'ai': 'info',
            'custom': 'warning'
        };
        return colors[type] || 'secondary';
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        return new Intl.DateTimeFormat('en-US', {
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        }).format(date);
    }

    function updateViewMode(mode) {
        const container = document.querySelector('#analytics-hub-page .row');
        if (mode === 'list') {
            container.classList.remove('g-4');
            container.classList.add('list-view');
        } else {
            container.classList.add('g-4');
            container.classList.remove('list-view');
        }
    }

    // Initialize
    updateQuickStats();
    loadRecentAnalytics();
    setInterval(updateQuickStats, 30000); // Update quick stats every 30 seconds

    // Export functionality
    function handleExport(e) {
        e.preventDefault();
        const loadingBtn = e.target.querySelector('button[type="submit"]');
        const originalText = loadingBtn?.innerHTML || 'Export';
        
        if (loadingBtn) {
            loadingBtn.disabled = true;
            loadingBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Exporting...';
        }

        const isVisualExport = document.getElementById('visual-export')?.checked;
        
        const formData = {
            type: document.getElementById('export-type')?.value,
            format: isVisualExport 
                ? document.getElementById('visual-format')?.value 
                : document.getElementById('data-format')?.value,
            dateRange: {
                start: dateRangeStart?.value,
                end: dateRangeEnd?.value
            }
        };
        
        if (isVisualExport) {
            formData.chartOptions = {
                type: chartTypeSelect?.value,
                theme: chartThemeSelect?.value
            };
        }

        fetch(`${API_BASE_URL}/export-analytics.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('token')}`,
                'Accept': 'application/json'
            },
            body: JSON.stringify(formData)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success' && data.fileUrl) {
                // Create download link
                const a = document.createElement('a');
                a.href = data.fileUrl;
                a.download = `analytics_export.${formData.format}`;
                document.body.appendChild(a);
                a.click();
                a.remove();

                // Reset form
                bsExportModal.hide();
                showToast('Export completed successfully!', 'success');
            } else {
                throw new Error(data.message || 'Export failed');
            }
        })
        .catch(error => {
            handleApiError(error, 'exporting analytics');
            showToast('Failed to export analytics. Please try again.', 'error');
        })
        .finally(() => {
            if (loadingBtn) {
                loadingBtn.disabled = false;
                loadingBtn.innerHTML = originalText;
            }
        });
    }

    // Attach export form handler
    if (exportForm) {
        exportForm.addEventListener('submit', handleExport);
    }

    // Handle export type toggle
    if (exportTypeRadios.length > 0) {
        exportTypeRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                const visualOptions = document.getElementById('visual-options');
                const dataOptions = document.getElementById('data-options');
                if (this.value === 'visual' && visualOptions && dataOptions) {
                    visualOptions.classList.remove('d-none');
                    dataOptions.classList.add('d-none');
                } else if (this.value === 'data' && visualOptions && dataOptions) {
                    visualOptions.classList.add('d-none');
                    dataOptions.classList.remove('d-none');
                }
            });
        });
    }

    // Set up chart preview listeners
    if (chartTypeSelect && chartThemeSelect) {
        [chartTypeSelect, chartThemeSelect].forEach(select => {
            select.addEventListener('change', previewChart);
        });
    }
});

// Analytics action handlers
function viewAnalytic(id) {
    window.location.href = `#analytics?id=${id}`;
}
window.viewAnalytic = viewAnalytic;

// Export functionality
function shareAnalytic(id) {
    // Implement sharing functionality
    console.log('Share analytic:', id);
}

function saveAnalytic(id) {
    fetch(SAVE_ANALYTIC_URL, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json'
        },
        body: JSON.stringify({ id })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.status === 'success') {
            showToast('Analytics saved successfully!', 'success');
        } else {
            showToast(data.message || 'Failed to save analytics', 'error');
        }
    })
    .catch(error => handleApiError(error, 'saving analytic'));
}

function showExportModal(type, description) {
    if (exportTypeSelect && exportModal) {
        exportTypeSelect.value = type;
        document.getElementById('export-description').textContent = description;
        exportModal.show();
    }
}

// Expose functions to window
window.shareAnalytic = shareAnalytic;
window.saveAnalytic = saveAnalytic;
window.showExportModal = showExportModal;

// Handle export type toggle
const exportTypeRadios = document.querySelectorAll('[name="export-type"]');
if (exportTypeRadios.length > 0) {
    exportTypeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            const dataGroup = document.getElementById('data-format-group');
            const visualGroup = document.getElementById('visual-format-group');
            
            if (this.value === 'data') {
                if (dataGroup) dataGroup.classList.remove('d-none');
                if (visualGroup) visualGroup.classList.add('d-none');
            } else {
                if (dataGroup) dataGroup.classList.add('d-none');
                if (visualGroup) visualGroup.classList.remove('d-none');
            }
        });
    });
    }

    // Preview chart when settings change
    const previewChart = () => {
        const chartType = document.getElementById('chart-type')?.value;
        const chartTheme = document.getElementById('chart-theme')?.value;
        
        if (chartType && chartTheme) {
            // TODO: Implement chart preview
            console.log('Chart preview:', { type: chartType, theme: chartTheme });
        }
    };

    // Set up chart preview listeners
    const chartTypeSelect = document.getElementById('chart-type');
    const chartThemeSelect = document.getElementById('chart-theme');
    
    if (chartTypeSelect) chartTypeSelect.addEventListener('change', previewChart);
    if (chartThemeSelect) chartThemeSelect.addEventListener('change', previewChart);

    // Initialize export form
    const exportFormHandler = function(e) {
        e.preventDefault();
        const loadingBtn = this.querySelector('button[type="submit"]');
        const originalText = loadingBtn?.innerHTML || 'Export';
        
        if (loadingBtn) {
            loadingBtn.disabled = true;
            loadingBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Exporting...';
        }

        const isVisualExport = document.getElementById('visual-export')?.checked;
        
        const formData = {
            type: document.getElementById('export-type')?.value,
            format: isVisualExport 
                ? document.getElementById('visual-format')?.value 
                : document.getElementById('data-format')?.value,
            dateRange: {
                start: dateRangeStart?.value,
                end: dateRangeEnd?.value
            }
        };
        
        if (isVisualExport) {
            formData.chartOptions = {
                type: document.getElementById('chart-type')?.value,
                theme: document.getElementById('chart-theme')?.value
            };
        }

        fetch(`${API_BASE_URL}/export-analytics.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('token')}`,
                'Accept': 'application/json'
            },
            body: JSON.stringify(formData)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success' && data.fileUrl) {
                // Create download link
                const a = document.createElement('a');
                a.href = data.fileUrl;
                a.download = `analytics_export.${formData.format}`;
                document.body.appendChild(a);
                a.click();
                a.remove();

                // Reset form
                bsExportModal.hide();
                showToast('Export completed successfully!', 'success');
            } else {
                throw new Error(data.message || 'Export failed');
            }
        })
        .catch(error => {
            handleApiError(error, 'exporting analytics');
            showToast('Failed to export analytics. Please try again.', 'error');
        })
        .finally(() => {
            if (loadingBtn) {
                loadingBtn.disabled = false;
                loadingBtn.innerHTML = originalText;
            }
        });
    };

    // Attach export form handler
    if (exportForm) {
        exportForm.addEventListener('submit', exportFormHandler);
            
            if (loadingBtn) {
                loadingBtn.disabled = true;
                loadingBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Exporting...';
            }

            const isVisualExport = document.getElementById('visual-export')?.checked;
            
            const formData = {
                type: document.getElementById('export-type')?.value,
                format: isVisualExport 
                    ? document.getElementById('visual-format')?.value 
                    : document.getElementById('data-format')?.value,
                dateRange: {
                    start: dateRangeStart?.value,
                    end: dateRangeEnd?.value
                }
            };
            
            if (isVisualExport) {
                formData.chartOptions = {
                    type: document.getElementById('chart-type')?.value,
                    theme: document.getElementById('chart-theme')?.value
                };
            }

            fetch(`${API_BASE_URL}/export-analytics.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem('token')}`,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(formData)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'success' && data.fileUrl) {
                    // Create download link
                    const a = document.createElement('a');
                    a.href = data.fileUrl;
                    a.download = `analytics_export.${formData.format}`;
                    document.body.appendChild(a);
                    a.click();
                    a.remove();

                    // Reset form
                    exportModal.hide();
                    showToast('Export completed successfully!', 'success');
                } else {
                    throw new Error(data.message || 'Export failed');
                }
            })
            .catch(error => {
                handleApiError(error, 'exporting analytics');
                showToast('Failed to export analytics. Please try again.', 'error');
            })
            .finally(() => {
                if (loadingBtn) {
                    loadingBtn.disabled = false;
                    loadingBtn.innerHTML = originalText;
                }
            });
        });
    }

    // Expose utility functions
    Object.assign(window, {
        shareAnalytic,
        saveAnalytic,
        showExportModal
    });
});

// Toast notification
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast align-items-center border-0 bg-${type} text-white`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    const container = document.getElementById('toast-container');
    if (container) {
        container.appendChild(toast);
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
        
        toast.addEventListener('hidden.bs.toast', () => toast.remove());
    }
}
