
// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize the charts when the data is available
    initDashboardCharts();
    
    // Initialize any datatables in the dashboard
    if ($.fn.DataTable) {
        $('.datatable').DataTable({
            language: {
                url: "//cdn.datatables.net/plug-ins/1.10.22/i18n/Vietnamese.json"
            },
            pageLength: 5,
            lengthMenu: [5, 10, 25, 50],
            ordering: true,
            responsive: true
        });
    }
    
    // Auto hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').alert('close');
    }, 5000);
    
    // Initialize tooltips
    if ($.fn.tooltip) {
        $('[data-toggle="tooltip"]').tooltip();
    }
});

// Function to initialize all dashboard charts
function initDashboardCharts(data) {
    // Set default data if not provided
    const chartData = data || {
        projectsByStatus: {
            inProgress: document.getElementById('projectProgressChart').dataset.inProgress || 0,
            completed: document.getElementById('projectProgressChart').dataset.completed || 0,
            waiting: document.getElementById('projectProgressChart').dataset.waiting || 0
        }
    };
    
    // Initialize project distribution chart (pie chart)
    const ctxPie = document.getElementById('projectDistributionChart');
    
    if (ctxPie) {
        new Chart(ctxPie, {
            type: 'doughnut',
            data: {
                labels: ["Đang tiến hành", "Đã hoàn thành", "Chờ phê duyệt"],
                datasets: [{
                    data: [
                        chartData.projectsByStatus.inProgress, 
                        chartData.projectsByStatus.completed, 
                        chartData.projectsByStatus.waiting
                    ],
                    backgroundColor: ['#4e73df', '#1cc88a', '#f6c23e'],
                    hoverBackgroundColor: ['#2e59d9', '#17a673', '#f4b619'],
                    hoverBorderColor: "rgba(234, 236, 244, 1)"
                }]
            },
            options: {
                maintainAspectRatio: false,
                legend: {
                    display: false
                },
                cutoutPercentage: 75,
                tooltips: {
                    backgroundColor: "rgb(255,255,255)",
                    bodyFontColor: "#858796",
                    borderColor: '#dddfeb',
                    borderWidth: 1,
                    xPadding: 15,
                    yPadding: 15,
                    displayColors: false,
                    caretPadding: 10
                }
            }
        });
    }
    
    // Initialize project progress chart (line chart)
    const ctxLine = document.getElementById('projectProgressChart');
    
    if (ctxLine) {
        new Chart(ctxLine, {
            type: 'line',
            data: {
                labels: ["Tháng 1", "Tháng 2", "Tháng 3", "Tháng 4", "Tháng 5", "Tháng 6"],
                datasets: [{
                    label: "Đề tài",
                    lineTension: 0.3,
                    backgroundColor: "rgba(78, 115, 223, 0.05)",
                    borderColor: "rgba(78, 115, 223, 1)",
                    pointRadius: 3,
                    pointBackgroundColor: "rgba(78, 115, 223, 1)",
                    pointBorderColor: "rgba(78, 115, 223, 1)",
                    pointHoverRadius: 3,
                    pointHoverBackgroundColor: "rgba(78, 115, 223, 1)",
                    pointHoverBorderColor: "rgba(78, 115, 223, 1)",
                    pointHitRadius: 10,
                    pointBorderWidth: 2,
                    // Replace with actual data when available
                    data: [0, 2, 5, 8, 12, 15]
                }]
            },
            options: {
                maintainAspectRatio: false,
                layout: {
                    padding: {
                        left: 10,
                        right: 25,
                        top: 25,
                        bottom: 0
                    }
                },
                scales: {
                    xAxes: [{
                        time: {
                            unit: 'date'
                        },
                        gridLines: {
                            display: false,
                            drawBorder: false
                        },
                        ticks: {
                            maxTicksLimit: 7
                        }
                    }],
                    yAxes: [{
                        ticks: {
                            maxTicksLimit: 5,
                            padding: 10,
                            callback: function(value) {
                                return value;
                            }
                        },
                        gridLines: {
                            color: "rgb(234, 236, 244)",
                            zeroLineColor: "rgb(234, 236, 244)",
                            drawBorder: false,
                            borderDash: [2],
                            zeroLineBorderDash: [2]
                        }
                    }]
                },
                legend: {
                    display: false
                },
                tooltips: {
                    backgroundColor: "rgb(255,255,255)",
                    bodyFontColor: "#858796",
                    titleMarginBottom: 10,
                    titleFontColor: '#6e707e',
                    titleFontSize: 14,
                    borderColor: '#dddfeb',
                    borderWidth: 1,
                    xPadding: 15,
                    yPadding: 15,
                    displayColors: false,
                    intersect: false,
                    mode: 'index',
                    caretPadding: 10
                }
            }
        });
    }
    
    // Initialize faculty projects chart (bar chart)
    const ctxBar = document.getElementById('facultyProjectsChart');
    
    if (ctxBar && data && data.facultyProjects) {
        const faculties = data.facultyProjects.map(item => item.name);
        const projectCounts = data.facultyProjects.map(item => item.count);
        
        new Chart(ctxBar, {
            type: 'horizontalBar',
            data: {
                labels: faculties,
                datasets: [{
                    label: "Số đề tài",
                    backgroundColor: "#4e73df",
                    hoverBackgroundColor: "#2e59d9",
                    borderColor: "#4e73df",
                    data: projectCounts
                }]
            },
            options: {
                maintainAspectRatio: false,
                layout: {
                    padding: {
                        left: 10,
                        right: 25,
                        top: 25,
                        bottom: 0
                    }
                },
                scales: {
                    xAxes: [{
                        gridLines: {
                            display: false,
                            drawBorder: false
                        },
                        ticks: {
                            beginAtZero: true
                        }
                    }],
                    yAxes: [{
                        gridLines: {
                            color: "rgb(234, 236, 244)",
                            zeroLineColor: "rgb(234, 236, 244)",
                            drawBorder: false,
                            borderDash: [2],
                            zeroLineBorderDash: [2]
                        }
                    }]
                },
                legend: {
                    display: false
                },
                tooltips: {
                    backgroundColor: "rgb(255,255,255)",
                    bodyFontColor: "#858796",
                    titleMarginBottom: 10,
                    titleFontColor: '#6e707e',
                    titleFontSize: 14,
                    borderColor: '#dddfeb',
                    borderWidth: 1,
                    xPadding: 15,
                    yPadding: 15,
                    displayColors: false,
                    intersect: false,
                    mode: 'index',
                    caretPadding: 10
                }
            }
        });
    }
}
