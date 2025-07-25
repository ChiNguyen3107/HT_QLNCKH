<?php
include '../../include/session.php';
checkStudentRole();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký thành công | Hệ thống NCKH</title>
    <!-- Favicon -->
    <link rel="icon" href="/NLNganh/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/NLNganh/favicon.ico" type="image/x-icon">
    
    <!-- CSS Libraries -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/animate.css@4.1.1/animate.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="/NLNganh/assets/css/student/style.css" rel="stylesheet">
</head>
<body id="page-top">
    <!-- Page Wrapper -->
    <div id="wrapper">
        <!-- Sidebar -->
        <?php include '../../include/student_sidebar.php'; ?>
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Main Content -->
            <div id="content">
                
                <!-- Begin Page Content -->
                <div class="container-fluid">
                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4 mt-4">
                        <h1 class="h3 mb-0 text-gray-800">
                            <i class="fas fa-check-circle text-success mr-2"></i>Đăng ký thành công
                        </h1>
                    </div>

                    <!-- Breadcrumb -->
                    <nav aria-label="breadcrumb" class="mb-4">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="student_dashboard.php"><i class="fas fa-home mr-1"></i>Trang chủ</a></li>
                            <li class="breadcrumb-item"><a href="register_project_form.php">Đăng ký đề tài</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Đăng ký thành công</li>
                        </ol>
                    </nav>
                    
                    <!-- Success Card -->
                    <div class="card shadow mb-4 animate__animated animate__fadeIn">
                        <div class="card-body text-center py-5">
                            <div class="success-icon mb-4">
                                <i class="fas fa-check-circle text-success fa-5x mb-3"></i>
                            </div>
                            <h2 class="text-success mb-4">Đăng ký đề tài thành công!</h2>
                            <p class="lead mb-4">Đề tài của bạn đã được gửi đến giảng viên hướng dẫn và cán bộ quản lý để xem xét phê duyệt.</p>
                            
                            <div class="card bg-light mb-4">
                                <div class="card-body">
                                    <h5 class="text-primary">Các bước tiếp theo</h5>
                                    <p class="mb-0">Giảng viên hướng dẫn sẽ xem xét đề tài của bạn trong vòng 3-7 ngày làm việc. Bạn sẽ nhận được thông báo khi đề tài được phê duyệt hoặc yêu cầu chỉnh sửa.</p>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <a href="student_dashboard.php" class="btn btn-primary btn-lg">
                                    <i class="fas fa-home mr-1"></i> Về trang chủ
                                </a>
                            </div>
                            <div class="mt-4">
                                <p class="text-muted">
                                    <i class="fas fa-clock mr-1"></i>
                                    Tự động chuyển về trang chủ sau <span id="countdown" class="badge badge-secondary">10</span> giây
                                </p>
                            </div>
                        </div>
                    </div>
                    
                </div>
                <!-- /.container-fluid -->
            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Hệ thống quản lý nghiên cứu khoa học &copy; 2023-2024</span>
                    </div>
                </div>
            </footer>
            <!-- End of Footer -->
        </div>
        <!-- End of Content Wrapper -->
    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="/NLNganh/assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        $(document).ready(function() {
            // Animation for success icon
            setTimeout(function() {
                $('.success-icon').addClass('animate__animated animate__tada');
            }, 500);
            
            // Hiển thị bộ đếm ngược
            let secondsLeft = 10;
            $('#countdown').text(secondsLeft);
            
            const countdownInterval = setInterval(function() {
                secondsLeft--;
                $('#countdown').text(secondsLeft);
                
                if (secondsLeft <= 0) {
                    clearInterval(countdownInterval);
                }
            }, 1000);
            
            // Tự động chuyển hướng về trang dashboard sau 10 giây
            setTimeout(function() {
                window.location.href = 'student_dashboard.php';
            }, 10000);
        });
    </script>
</body>
</html>