/**
 * Dashboard Analytics Module - Document Tracking System
 * Handles data collection and rendering for real-time tracking graphs.
 */
document.addEventListener('DOMContentLoaded', function() {
    initStatusChart();
    initDepartmentChart();

    /**
     * Build the Status Distribution Doughnut Chart
     */
    function initStatusChart() {
        const canvas = document.getElementById('statusChart');
        if (!canvas) return;

        try {
            // Harvest raw JSON payload passed through the HTML data attribute
            const rawData = JSON.parse(canvas.getAttribute('data-metrics') || '{}');
            
            // Standardize object keys to handle capitalization matching your schema choices
            const labels = Object.keys(rawData).map(label => label.toUpperCase().replace('_', ' '));
            const dataValues = Object.values(rawData);

            new Chart(canvas, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: dataValues,
                        backgroundColor: [
                            '#198754', // RECEIVED / COMPLETED (Green)
                            '#0dcaf0', // IN TRANSIT (Teal)
                            '#ffc107', // PENDING (Yellow)
                            '#dc3545'  // REJECTED (Red)
                        ],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12,
                                font: { size: 11, family: 'Inter, sans-serif' },
                                padding: 15
                            }
                        }
                    }
                }
            });
        } catch (error) {
            console.error('[Dashboard Module Alert] Failed to initialize Status Chart components:', error);
        }
    }

    /**
     * Build the Departmental Volume Bar Chart
     */
    function initDepartmentChart() {
        const canvas = document.getElementById('departmentChart');
        if (!canvas) return;

        try {
            const rawData = JSON.parse(canvas.getAttribute('data-metrics') || '{}');
            
            const labels = Object.keys(rawData);
            const dataValues = Object.values(rawData);

            new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Active Tracking Load',
                        data: dataValues,
                        backgroundColor: '#0d6efd',
                        borderRadius: 4,
                        barThickness: 24
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { stepSize: 1, font: { size: 10 } },
                            grid: { borderDash: [4, 4] }
                        },
                        x: {
                            ticks: { font: { size: 10 } },
                            grid: { display: false }
                        }
                    }
                }
            });
        } catch (error) {
            console.error('[Dashboard Module Alert] Failed to initialize Department Chart components:', error);
        }
    }
});
