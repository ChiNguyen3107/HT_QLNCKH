// Biểu đồ bài báo khoa học qua các năm
if (document.getElementById('publicationChart')) {
    const publicationChartCtx = document.getElementById('publicationChart').getContext('2d');
    const publicationChart = new Chart(publicationChartCtx, {
        type: 'line',
        data: {
            labels: pubYears,
            datasets: [{
                label: 'Số lượng bài báo',
                data: pubCounts,
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 2,
                pointBackgroundColor: 'rgba(54, 162, 235, 1)',
                pointBorderColor: '#fff',
                pointBorderWidth: 1,
                pointRadius: 5,
                tension: 0.1
            }]
        },
        options: {
            maintainAspectRatio: false,
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero: true,
                        stepSize: 1
                    }
                }]
            }
        }
    });
}

// Biểu đồ sinh viên tham gia
if (document.getElementById('participationChart')) {
    const participationChartCtx = document.getElementById('participationChart').getContext('2d');
    const participationChart = new Chart(participationChartCtx, {
        type: 'pie',
        data: {
            labels: participationLabels,
            datasets: [{
                data: participationData,
                backgroundColor: [
                    'rgba(28, 200, 138, 0.7)',
                    'rgba(231, 74, 59, 0.7)'
                ],
                borderColor: [
                    'rgb(28, 200, 138)',
                    'rgb(231, 74, 59)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            maintainAspectRatio: false,
            legend: {
                position: 'bottom'
            }
        }
    });
}

// Biểu đồ phân bố theo kinh phí
if (document.getElementById('fundingChart')) {
    const fundingChartCtx = document.getElementById('fundingChart').getContext('2d');
    const fundingChart = new Chart(fundingChartCtx, {
        type: 'bar',
        data: {
            labels: fundingRanges,
            datasets: [{
                label: 'Số lượng đề tài',
                data: fundingCounts,
                backgroundColor: 'rgba(246, 194, 62, 0.7)',
                borderColor: 'rgb(246, 194, 62)',
                borderWidth: 1
            }]
        },
        options: {
            maintainAspectRatio: false,
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero: true
                    }
                }]
            }
        }
    });
}

// Biểu đồ thời gian hoàn thành đề tài
if (document.getElementById('completionChart')) {
    const completionChartCtx = document.getElementById('completionChart').getContext('2d');
    const completionChart = new Chart(completionChartCtx, {
        type: 'horizontalBar',
        data: {
            labels: completionRanges,
            datasets: [{
                label: 'Số lượng đề tài',
                data: completionCounts,
                backgroundColor: 'rgba(78, 115, 223, 0.7)',
                borderColor: 'rgb(78, 115, 223)',
                borderWidth: 1
            }]
        },
        options: {
            maintainAspectRatio: false,
            scales: {
                xAxes: [{
                    ticks: {
                        beginAtZero: true
                    }
                }]
            }
        }
    });
}

// Cập nhật xử lý sự kiện xuất biểu đồ
$('#exportMonthlyChart, #exportStatusChart, #exportTypeChart, #exportFacultyChart, #exportPublicationChart, #exportParticipationChart, #exportFundingChart, #exportCompletionChart').click(function() {
    const chartId = $(this).attr('id').replace('export', '');
    exportChart(chartId.replace('Chart', '').toLowerCase());
});

// Cập nhật xử lý sự kiện xuất bảng
$('#exportTeacherTable, #exportStudentTable, #exportFundingDetail').click(function() {
    const tableType = $(this).attr('id').replace('export', '').replace('Table', '').replace('Detail', '').toLowerCase();
    exportTable(tableType);
});
