document.addEventListener('DOMContentLoaded', function() {
    // Biến nhận dữ liệu từ PHP - sẽ được thiết lập bởi PHP
    let statusData, deptData, typeData;
    
    // Hàm khởi tạo biểu đồ trạng thái
    function initStatusChart() {
        const hasStatusData = statusData.labels.length > 0 && statusData.labels[0] !== 'Không có dữ liệu';
        let statusChartEl = document.getElementById('statusChart');
        
        if (!hasStatusData) {
            statusChartEl.parentNode.innerHTML = '<div class="text-center py-5"><i class="fas fa-chart-pie fa-3x text-gray-300 mb-3"></i><p>Không có dữ liệu để hiển thị</p></div>';
        } else {
            // Tạo biểu đồ nếu có dữ liệu
            var statusChart = new Chart(statusChartEl, {
                type: 'pie',
                data: {
                    labels: statusData.labels,
                    datasets: [{
                        data: statusData.data,
                        backgroundColor: [
                            '#f6c23e', // Chờ duyệt
                            '#4e73df', // Đang thực hiện
                            '#1cc88a', // Đã hoàn thành
                            '#36b9cc', // Tạm dừng
                            '#e74a3b'  // Đã hủy
                        ],
                        hoverBackgroundColor: [
                            '#e0ae36',
                            '#4668c9',
                            '#19b77e',
                            '#30a7b9',
                            '#d64433'
                        ]
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    tooltips: {
                        backgroundColor: "rgb(255,255,255)",
                        bodyFontColor: "#858796",
                        borderColor: '#dddfeb',
                        borderWidth: 1,
                        xPadding: 15,
                        yPadding: 15,
                        displayColors: false,
                        caretPadding: 10
                    },
                    legend: {
                        display: true,
                        position: 'bottom'
                    }
                }
            });
        }
    }

    // Hàm khởi tạo biểu đồ khoa
    function initDepartmentChart() {
        const hasDeptData = deptData.labels.length > 0 && deptData.labels[0] !== 'Không có dữ liệu';
        let deptChartEl = document.getElementById('departmentChart');
        
        if (!hasDeptData) {
            deptChartEl.parentNode.innerHTML = '<div class="text-center py-5"><i class="fas fa-building fa-3x text-gray-300 mb-3"></i><p>Không có dữ liệu để hiển thị</p></div>';
        } else {
            // Tạo biểu đồ phân bố theo khoa
            var deptChart = new Chart(deptChartEl, {
                type: 'bar',
                data: {
                    labels: deptData.labels,
                    datasets: [{
                        label: 'Số đề tài',
                        data: deptData.data,
                        backgroundColor: '#4e73df',
                        hoverBackgroundColor: '#3b60c1',
                        borderWidth: 1
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
                                maxTicksLimit: 10
                            },
                            maxBarThickness: 40,
                        }],
                        yAxes: [{
                            ticks: {
                                min: 0,
                                maxTicksLimit: 5,
                                padding: 10
                            },
                            gridLines: {
                                color: "rgb(234, 236, 244)",
                                zeroLineColor: "rgb(234, 236, 244)",
                                drawBorder: false,
                                borderDash: [2],
                                zeroLineBorderDash: [2]
                            }
                        }],
                    },
                    tooltips: {
                        titleMarginBottom: 10,
                        titleFontColor: '#6e707e',
                        titleFontSize: 14,
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
    }

    // Hàm khởi tạo biểu đồ loại đề tài
    function initTypeChart() {
        const hasTypeData = typeData.labels.length > 0 && typeData.labels[0] !== 'Không có dữ liệu';
        let typeChartEl = document.getElementById('typeChart');
        
        if (!hasTypeData) {
            typeChartEl.parentNode.innerHTML = '<div class="text-center py-5"><i class="fas fa-list fa-3x text-gray-300 mb-3"></i><p>Không có dữ liệu để hiển thị</p></div>';
        } else {
            // Biểu đồ phân bố theo loại đề tài
            var typeChart = new Chart(typeChartEl, {
                type: 'horizontalBar',
                data: {
                    labels: typeData.labels,
                    datasets: [{
                        label: 'Số đề tài',
                        data: typeData.data,
                        backgroundColor: '#36b9cc',
                        hoverBackgroundColor: '#30a7b9',
                        borderWidth: 1
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
                            ticks: {
                                min: 0,
                                maxTicksLimit: 5,
                                padding: 10
                            },
                            gridLines: {
                                color: "rgb(234, 236, 244)",
                                zeroLineColor: "rgb(234, 236, 244)",
                                drawBorder: false,
                                borderDash: [2],
                                zeroLineBorderDash: [2]
                            }
                        }],
                        yAxes: [{
                            gridLines: {
                                display: false,
                                drawBorder: false
                            },
                            maxBarThickness: 40
                        }]
                    },
                    tooltips: {
                        titleMarginBottom: 10,
                        titleFontColor: '#6e707e',
                        titleFontSize: 14,
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
    }

    // Xử lý xuất báo cáo
    document.getElementById('exportExcel').addEventListener('click', function() {
        alert('Chức năng xuất Excel sẽ được triển khai trong tương lai');
        // Đoạn code xuất Excel sẽ được thêm vào đây
    });

    document.getElementById('exportPDF').addEventListener('click', function() {
        alert('Chức năng xuất PDF sẽ được triển khai trong tương lai');
        // Đoạn code xuất PDF sẽ được thêm vào đây
    });

    document.getElementById('exportCSV').addEventListener('click', function() {
        alert('Chức năng xuất CSV sẽ được triển khai trong tương lai');
        // Đoạn code xuất CSV sẽ được thêm vào đây
    });
    
    // Hàm khởi tạo tất cả biểu đồ
    window.initCharts = function(status, dept, type) {
        statusData = status;
        deptData = dept;
        typeData = type;
        
        initStatusChart();
        initDepartmentChart();
        initTypeChart();
    };
});