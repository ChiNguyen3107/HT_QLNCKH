/**
 * JavaScript cho trang báo cáo và thống kê sinh viên
 */

$(document).ready(function() {
    // Khởi tạo tooltip Bootstrap
    $('[data-toggle="tooltip"]').tooltip();
    
    // Hiệu ứng khi trang tải xong
    animateDashboard();
    
    // Khởi tạo biểu đồ theo đề tài
    if (typeof projectLabels !== 'undefined' && projectLabels.length > 0) {
        initProjectReportsChart();
    }
    
    // Khởi tạo biểu đồ điểm số
    if (typeof scoreLabels !== 'undefined' && scoreLabels.length > 0) {
        initScoreChart();
    }
    
    /**
     * Hiệu ứng cho dashboard
     */
    function animateDashboard() {
        // Hiệu ứng fade-in cho các thẻ thống kê
        $('.stats-card').each(function(index) {
            $(this).css('opacity', 0).delay(100 * index).animate({
                opacity: 1
            }, 500);
        });
        
        // Hiệu ứng cho bảng báo cáo
        $('.card').css('opacity', 0).delay(300).animate({
            opacity: 1
        }, 500);
    }
    
    /**
     * Biểu đồ thống kê báo cáo theo đề tài
     */
    function initProjectReportsChart() {
        const ctx = document.getElementById('reportsByProjectChart').getContext('2d');
        
        const reportsByProjectChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: projectLabels,
                datasets: [
                    {
                        label: 'Đã duyệt',
                        backgroundColor: '#1cc88a',
                        data: approvedData
                    },
                    {
                        label: 'Chờ duyệt',
                        backgroundColor: '#f6c23e',
                        data: pendingData
                    },
                    {
                        label: 'Cần chỉnh sửa',
                        backgroundColor: '#e74a3b',
                        data: revisionData
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        stacked: true,
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                }
            }
        });
    }
    
    /**
     * Biểu đồ điểm số báo cáo
     */
    function initScoreChart() {
        const ctx = document.getElementById('scoreChart').getContext('2d');
        
        const gradientFill = ctx.createLinearGradient(0, 0, 0, 200);
        gradientFill.addColorStop(0, 'rgba(78, 115, 223, 0.4)');
        gradientFill.addColorStop(1, 'rgba(78, 115, 223, 0)');
        
        const scoreChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: scoreLabels,
                datasets: [
                    {
                        label: 'Điểm',
                        data: scoreData,
                        backgroundColor: gradientFill,
                        borderColor: '#4e73df',
                        borderWidth: 2,
                        pointBackgroundColor: '#4e73df',
                        pointBorderColor: '#fff',
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: '#4e73df',
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        tension: 0.3,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 10,
                        ticks: {
                            callback: function(value) {
                                return value.toFixed(1);
                            }
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
                                return `Điểm: ${context.parsed.y.toFixed(1)}`;
                            }
                        }
                    }
                }
            }
        });
    }
    
    /**
     * Hiệu ứng khi hover vào bảng
     */
    $('.table-hover tbody tr').hover(
        function() {
            $(this).addClass('bg-light');
        }, 
        function() {
            $(this).removeClass('bg-light');
        }
    );
    
    /**
     * Xử lý sự kiện click vào nút tải xuống
     */
    $('.download-report-btn').click(function(e) {
        const url = $(this).data('url');
        if (!url || url === '') {
            e.preventDefault();
            alert('Không tìm thấy file báo cáo');
        }
    });
    
    /**
     * Xử lý lọc báo cáo theo đề tài
     */
    $('#filterProject').change(function() {
        const projectId = $(this).val();
        
        if (projectId === 'all') {
            $('.report-row').show();
        } else {
            $('.report-row').hide();
            $(`.report-row[data-project="${projectId}"]`).show();
        }
    });
    
    /**
     * Xử lý lọc báo cáo theo trạng thái
     */
    $('#filterStatus').change(function() {
        const status = $(this).val();
        
        if (status === 'all') {
            $('.report-row').show();
        } else {
            $('.report-row').hide();
            $(`.report-row[data-status="${status}"]`).show();
        }
    });
    
    /**
     * Xử lý tìm kiếm báo cáo
     */
    $('#searchReport').on('input', function() {
        const searchText = $(this).val().toLowerCase();
        
        if (searchText === '') {
            $('.report-row').show();
        } else {
            $('.report-row').each(function() {
                const reportName = $(this).find('.report-name').text().toLowerCase();
                const projectName = $(this).find('.project-name').text().toLowerCase();
                
                if (reportName.includes(searchText) || projectName.includes(searchText)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        }
    });
});