document.addEventListener('DOMContentLoaded', function() {
    // Hàm khởi tạo biểu đồ trạng thái đề tài
    function initProjectStatusChart(projectStats) {
        var ctx = document.getElementById('projectStatusChart').getContext('2d');
        
        // Kiểm tra nếu không có dữ liệu
        const hasData = Object.values(projectStats).some(value => value > 0);
        
        if (!hasData) {
            // Hiển thị thông báo khi không có dữ liệu
            const chartContainer = ctx.canvas.parentNode;
            chartContainer.innerHTML = '<div class="text-center py-5"><i class="fas fa-chart-pie fa-3x text-gray-300 mb-3"></i><p>Không có dữ liệu để hiển thị</p></div>';
            return;
        }
        
        var projectStatusChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: [
                    'Chờ duyệt',
                    'Đang thực hiện',
                    'Đã hoàn thành',
                    'Tạm dừng',
                    'Đã hủy'
                ],
                datasets: [{
                    data: [
                        projectStats['Chờ duyệt'],
                        projectStats['Đang thực hiện'],
                        projectStats['Đã hoàn thành'],
                        projectStats['Tạm dừng'],
                        projectStats['Đã hủy']
                    ],
                    backgroundColor: [
                        '#ffc107',
                        '#007bff',
                        '#28a745',
                        '#17a2b8',
                        '#dc3545'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                    title: {
                        display: true,
                        text: 'Phân bố trạng thái đề tài'
                    }
                }
            }
        });
    }

    // Hàm này sẽ được gọi từ admin_dashboard.php để khởi tạo biểu đồ
    window.initDashboardCharts = function(projectStats) {
        initProjectStatusChart(projectStats);
    };
    
    // Thêm animation cho các phần tử
    const animateElements = () => {
        // Hiệu ứng hiển thị dần các thẻ thống kê
        const statCards = document.querySelectorAll('.stat-card-link');
        statCards.forEach((card, index) => {
            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 100 * index);
        });
        
        // Hiệu ứng hiển thị dần các liên kết nhanh
        const quickLinks = document.querySelectorAll('.quick-links .list-group-item');
        quickLinks.forEach((link, index) => {
            setTimeout(() => {
                link.style.opacity = '1';
                link.style.transform = 'translateX(0)';
            }, 150 * index);
        });
    };
    
    // Thực hiện animation
    animateElements();
});