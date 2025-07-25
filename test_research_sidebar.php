<?php
session_start();

// Set test research manager session
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'research_manager';
$_SESSION['role'] = 'research_manager';
$_SESSION['fullname'] = 'Quản lý Nghiên cứu';

// Include database connection
include 'include/connect.php';
include 'include/research_header.php';
?>

<div id="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-vials mr-2"></i>
                            Test Research Manager Dashboard
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Welcome Card -->
                            <div class="col-xl-6 col-md-6 mb-4">
                                <div class="card border-left-primary shadow h-100 py-2">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                    Chào mừng
                                                </div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                    <?php echo $_SESSION['fullname']; ?>
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-user fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Sidebar Status -->
                            <div class="col-xl-6 col-md-6 mb-4">
                                <div class="card border-left-success shadow h-100 py-2">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                    Sidebar Status
                                                </div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                    Hoạt động tốt
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Test Navigation -->
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="m-0 font-weight-bold text-primary">
                                            <i class="fas fa-list mr-2"></i>
                                            Danh sách chức năng Sidebar
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th>Chức năng</th>
                                                        <th>Icon</th>
                                                        <th>Trạng thái</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>Dashboard</td>
                                                        <td><i class="fas fa-tachometer-alt text-primary"></i></td>
                                                        <td><span class="badge badge-success">Hoạt động</span></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Quản lý dự án</td>
                                                        <td><i class="fas fa-project-diagram text-info"></i></td>
                                                        <td><span class="badge badge-success">Hoạt động</span></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Quản lý nhà nghiên cứu</td>
                                                        <td><i class="fas fa-users text-warning"></i></td>
                                                        <td><span class="badge badge-success">Hoạt động</span></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Báo cáo & Thống kê</td>
                                                        <td><i class="fas fa-chart-bar text-success"></i></td>
                                                        <td><span class="badge badge-success">Hoạt động</span></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Cấu hình hệ thống</td>
                                                        <td><i class="fas fa-cogs text-secondary"></i></td>
                                                        <td><span class="badge badge-success">Hoạt động</span></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'include/research_footer.php'; ?>

<style>
.content-wrapper {
    margin-left: 250px;
    transition: margin-left 0.3s ease;
}

@media (max-width: 768px) {
    .content-wrapper {
        margin-left: 0;
    }
}

.card {
    border-radius: 10px;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
}

.card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 10px 10px 0 0 !important;
}

.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}

.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}

.table th {
    background-color: #f8f9fc;
    border-color: #e3e6f0;
    font-weight: 600;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.badge {
    font-size: 0.75rem;
    padding: 0.375rem 0.75rem;
}
</style>
