/**
 * Audit Dashboard Analytics Module - Document Tracking System
 * Initializes all four Chart.js visualizations on the Audit Portal dashboard.
 */
document.addEventListener('DOMContentLoaded', function() {
    initAuditStatusChart();
    initAuditDepartmentChart();
    initAuditSlaBottleneckChart();
    initAuditIssuesByDeptChart();

    /**
     * Status Distribution Doughnut — global document lifecycle states
     */
    function initAuditStatusChart() {
        const canvas = document.getElementById('auditStatusChart');
        if (!canvas) return;

        try {
            const rawData = JSON.parse(canvas.getAttribute('data-metrics') || '{}');
            const labels = Object.keys(rawData).map(label => label.toUpperCase().replace(/_/g, ' '));
            const dataValues = Object.values(rawData);

            const colorMap = {
                'RECEIVED':         '#198754',
                'COMPLETED':        '#198754',
                'IN TRANSIT':       '#0dcaf0',
                'PENDING TRANSFER': '#ffc107',
                'REJECTED':         '#dc3545',
                'CANCELLED':        '#6c757d',
            };

            new Chart(canvas, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: dataValues,
                        backgroundColor: labels.map(label => colorMap[label] || '#6c757d'),
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
            console.error('[Audit Dashboard] Failed to initialize Status Chart:', error);
        }
    }

    /**
     * Department Workload Bar Chart — active documents per department
     */
    function initAuditDepartmentChart() {
        const canvas = document.getElementById('auditDepartmentChart');
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
                        label: 'Active Documents',
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
                            ticks: { font: { size: 10, family: "'Inter', 'Segoe UI', sans-serif" } },
                            grid: { display: false }
                        }
                    }
                }
            });
        } catch (error) {
            console.error('[Audit Dashboard] Failed to initialize Department Chart:', error);
        }
    }

    /**
     * SLA Bottleneck Horizontal Bar — overdue steps grouped by department
     */
    function initAuditSlaBottleneckChart() {
        const canvas = document.getElementById('auditSlaBottleneckChart');
        if (!canvas) return;

        try {
            const rawData = JSON.parse(canvas.getAttribute('data-metrics') || '{}');
            const labels = Object.keys(rawData);
            const dataValues = Object.values(rawData);

            if (dataValues.length === 0) {
                canvas.parentElement.innerHTML = '<div class="text-center text-muted p-4"><small>No system delays detected this month. Great job!</small></div>';
                return;
            }

            new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Overdue Documents',
                        data: dataValues,
                        backgroundColor: '#dc3545', // Danger red for SLA breaches
                        borderRadius: 4,
                        maxBarThickness: 40 // Prevents the bar from becoming too thick when there are few items
                    }]
                },
                options: {
                    indexAxis: 'y', // Renders as horizontal bar chart
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        x: { beginAtZero: true, ticks: { stepSize: 1 } }
                    }
                }
            });
        } catch (error) {
            console.error('[Audit Dashboard] Failed to init SLA Bottleneck Chart:', error);
        }
    }

    /**
     * Issues by Department Horizontal Bar — top departments by issue volume
     */
    function initAuditIssuesByDeptChart() {
        const canvas = document.getElementById('auditIssuesByDeptChart');
        if (!canvas) return;

        try {
            const rawData = JSON.parse(canvas.getAttribute('data-metrics') || '{}');
            const labels = Object.keys(rawData);
            const dataValues = Object.values(rawData);

            if (dataValues.length === 0) {
                canvas.parentElement.innerHTML = '<div class="text-center text-muted p-4"><small>No department issues recorded yet.</small></div>';
                return;
            }

            new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Reported Issues',
                        data: dataValues,
                        backgroundColor: '#dc3545',
                        borderRadius: 4,
                        barThickness: 20
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: { stepSize: 1, font: { size: 10 } },
                            grid: { borderDash: [4, 4] }
                        },
                        y: {
                            ticks: { font: { size: 10, family: "'Inter', 'Segoe UI', sans-serif" } },
                            grid: { display: false }
                        }
                    }
                }
            });
        } catch (error) {
            console.error('[Audit Dashboard] Failed to initialize Issues by Department Chart:', error);
        }
    }
});
