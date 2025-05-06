/**
 * Dashboard Charts
 * 
 * This file contains the code for generating charts and visualizations
 * on the admin dashboard.
 */

// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('Dashboard Charts JS loaded');
    
    // Initialize charts if we're on the dashboard page
    if (document.querySelector('.dashboard-cards')) {
        initContentStatisticsChart();
        initActivityTimelineChart();
        initContentDistributionChart();
        initUserActivityChart();
    }
});

/**
 * Initialize the content statistics chart
 */
function initContentStatisticsChart() {
    // Check if Chart.js is loaded
    if (typeof Chart === 'undefined') {
        console.warn('Chart.js is not loaded. Charts will not be displayed.');
        return;
    }
    
    const chartContainer = document.getElementById('content-stats-chart');
    if (!chartContainer) return;
    
    // Get data from dashboard cards
    const labels = [];
    const data = [];
    const colors = [];
    
    document.querySelectorAll('.dashboard-card').forEach(card => {
        const titleElement = card.querySelector('h3');
        const valueElement = card.querySelector('.stat-number');
        
        if (titleElement && valueElement) {
            const title = titleElement.textContent.trim().replace(/^\s*\S+\s+/, ''); // Remove icon
            const value = parseInt(valueElement.textContent.trim(), 10);
            
            if (!isNaN(value)) {
                labels.push(title);
                data.push(value);
                
                // Assign colors based on card type
                if (card.classList.contains('user-card')) {
                    colors.push('#10b981'); // success color
                } else if (card.classList.contains('media-card')) {
                    colors.push('#3b82f6'); // info color
                } else if (card.classList.contains('notification-card')) {
                    colors.push('#f59e0b'); // warning color
                } else {
                    colors.push('#4361ee'); // primary color
                }
            }
        }
    });
    
    new Chart(chartContainer, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Content Count',
                data: data,
                backgroundColor: colors,
                borderColor: colors.map(color => color),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.parsed.y + ' items';
                        }
                    }
                }
            }
        }
    });
}

/**
 * Initialize the activity timeline chart
 */
function initActivityTimelineChart() {
    // Check if Chart.js is loaded
    if (typeof Chart === 'undefined') return;
    
    const chartContainer = document.getElementById('activity-timeline-chart');
    if (!chartContainer) return;
    
    // Sample data - in a real implementation, this would come from the server
    const dates = [];
    const now = new Date();
    
    for (let i = 6; i >= 0; i--) {
        const date = new Date(now);
        date.setDate(date.getDate() - i);
        dates.push(date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
    }
    
    new Chart(chartContainer, {
        type: 'line',
        data: {
            labels: dates,
            datasets: [{
                label: 'Stories',
                data: [3, 5, 2, 7, 4, 6, 8],
                borderColor: '#4361ee',
                backgroundColor: 'rgba(67, 97, 238, 0.1)',
                tension: 0.4,
                fill: true
            }, {
                label: 'Blog Posts',
                data: [1, 2, 4, 2, 3, 5, 4],
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                tension: 0.4,
                fill: true
            }, {
                label: 'Media',
                data: [2, 3, 1, 4, 5, 3, 6],
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            },
            plugins: {
                tooltip: {
                    mode: 'index',
                    intersect: false
                }
            }
        }
    });
}

/**
 * Initialize the content distribution chart
 */
function initContentDistributionChart() {
    // Check if Chart.js is loaded
    if (typeof Chart === 'undefined') return;
    
    const chartContainer = document.getElementById('content-distribution-chart');
    if (!chartContainer) return;
    
    // Sample data - in a real implementation, this would come from the server
    new Chart(chartContainer, {
        type: 'doughnut',
        data: {
            labels: ['Stories', 'Blog Posts', 'Authors', 'Media', 'Games', 'AI Tools'],
            datasets: [{
                data: [35, 20, 15, 25, 5, 10],
                backgroundColor: [
                    '#4361ee', // primary
                    '#10b981', // success
                    '#f59e0b', // warning
                    '#3b82f6', // info
                    '#ef4444', // danger
                    '#8b5cf6'  // purple
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}

/**
 * Initialize the user activity chart
 */
function initUserActivityChart() {
    // Check if Chart.js is loaded
    if (typeof Chart === 'undefined') return;
    
    const chartContainer = document.getElementById('user-activity-chart');
    if (!chartContainer) return;
    
    // Sample data - in a real implementation, this would come from the server
    const hours = [];
    for (let i = 0; i < 24; i++) {
        hours.push(i + ':00');
    }
    
    new Chart(chartContainer, {
        type: 'bar',
        data: {
            labels: hours,
            datasets: [{
                label: 'User Activity',
                data: [5, 3, 2, 1, 0, 0, 2, 5, 10, 15, 20, 25, 30, 28, 25, 20, 18, 15, 12, 10, 8, 6, 4, 3],
                backgroundColor: '#4361ee',
                borderColor: '#3a56d4',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.parsed.y + ' users';
                        }
                    }
                }
            }
        }
    });
}
