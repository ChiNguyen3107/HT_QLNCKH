<?php
// filepath: d:\xampp\htdocs\NLNganh\include\research_header.php
// Common header file for research manager pages
// Contains standard CSS and JS includes and responsive design elements

// Don't output anything if included in another file
if (basename($_SERVER['PHP_SELF']) === 'research_header.php') {
    header("Location: /NLNganh/login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Quản lý Nghiên cứu Khoa học</title>
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="/NLNganh/favicon.ico" type="image/x-icon">
    <link rel="icon" href="/NLNganh/favicon.ico" type="image/x-icon">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- SB Admin 2 CSS - Main framework -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/startbootstrap-sb-admin-2/4.1.3/css/sb-admin-2.min.css" rel="stylesheet">
    
    <!-- Modern Sidebar Layout CSS -->
    <link href="/NLNganh/assets/css/research/modern-sidebar-layout.css" rel="stylesheet">
    
    <!-- Simple Sidebar CSS -->
    <link href="/NLNganh/assets/css/research/simple-sidebar.css" rel="stylesheet">
    
    <!-- Simple Sidebar Enhanced CSS -->
    <link href="/NLNganh/assets/css/research/simple-sidebar-enhanced.css" rel="stylesheet">
    
    <!-- Modern Sidebar Custom CSS -->
    <link href="/NLNganh/assets/css/research/modern-sidebar-custom.css" rel="stylesheet">
    
    <!-- Research Manager Custom CSS -->
    <link href="/NLNganh/assets/css/research/research-unified.css" rel="stylesheet">
    <link href="/NLNganh/assets/css/research/research-tables.css" rel="stylesheet">
    <link href="/NLNganh/assets/css/research/dashboard.css" rel="stylesheet">
    <link href="/NLNganh/assets/css/research/responsive.css" rel="stylesheet">
    <link href="/NLNganh/assets/css/research/project-manager.css" rel="stylesheet">
    <link href="/NLNganh/assets/css/research/sidebar-dropdown-fix.css" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    
    <!-- Chart.js CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.css">
    
    <!-- Page specific CSS can be added here -->
    <?php if (isset($additional_css)): ?>
        <?php echo $additional_css; ?>
    <?php endif; ?>
    
    <!-- Layout Fix CSS to prevent sidebar overlap -->
    <style>
        #wrapper {
            display: flex !important;
        }
        
        .simple-sidebar {
            position: fixed !important;
            z-index: 1000 !important;
        }
        
        #content-wrapper {
            margin-left: 250px !important;
            width: calc(100% - 250px) !important;
            flex: 1 !important;
        }
        
        @media (max-width: 768px) {
            #content-wrapper {
                margin-left: 0 !important;
                width: 100% !important;
            }
        }
    </style>
</head>
<body id="page-top">
    <div id="wrapper">
    <!-- Simple Research Sidebar -->
    <?php include __DIR__ . '/simple_research_sidebar.php'; ?>
    
    <!-- Content Wrapper -->
    <div id="content-wrapper" class="d-flex flex-column content-wrapper">
        <!-- Main Content -->
        <div id="content">
            <!-- Topbar -->
            <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                <!-- Sidebar Toggle (Topbar) -->
                <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3" onclick="toggleMobileSidebar()">
                    <i class="fa fa-bars"></i>
                </button>

                <!-- Search form here -->
                <form class="d-none d-sm-inline-block form-inline mr-auto ml-md-3 my-2 my-md-0 mw-100 navbar-search" action="/NLNganh/view/research/manage_projects.php" method="GET">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control bg-light border-0 small" placeholder="Tìm kiếm..." aria-label="Tìm kiếm" aria-describedby="basic-addon2">
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-search fa-sm"></i>
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Topbar Navbar -->
                <ul class="navbar-nav ml-auto">
                    <!-- Nav Item - Search Dropdown (Visible Only XS) -->
                    <li class="nav-item dropdown no-arrow d-sm-none">
                        <a class="nav-link dropdown-toggle" href="#" id="searchDropdown" role="button"
                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-search fa-fw"></i>
                        </a>
                        <!-- Dropdown - Messages -->
                        <div class="dropdown-menu dropdown-menu-right p-3 shadow animated--grow-in"
                            aria-labelledby="searchDropdown">
                            <form class="form-inline mr-auto w-100 navbar-search" action="/NLNganh/view/research/manage_projects.php" method="GET">
                                <div class="input-group">
                                    <input type="text" name="search" class="form-control bg-light border-0 small"
                                        placeholder="Tìm kiếm..." aria-label="Tìm kiếm"
                                        aria-describedby="basic-addon2">
                                    <div class="input-group-append">
                                        <button class="btn btn-primary" type="submit">
                                            <i class="fas fa-search fa-sm"></i>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </li>

                    <!-- Nav Item - Notifications -->
                    <li class="nav-item dropdown no-arrow mx-1">
                        <a class="nav-link dropdown-toggle" href="#" id="alertsDropdown" role="button"
                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-bell fa-fw"></i>
                            <!-- Counter - Notifications -->
                            <span class="badge badge-danger badge-counter" id="notification-count">0</span>
                        </a>
                        <!-- Dropdown - Notifications -->
                        <div class="dropdown-list dropdown-menu dropdown-menu-right shadow animated--grow-in"
                            aria-labelledby="alertsDropdown">
                            <h6 class="dropdown-header">
                                Thông báo
                            </h6>
                            <div id="notifications-container">
                                <!-- Notifications will be loaded here -->
                                <div class="text-center p-3">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="sr-only">Loading...</span>
                                    </div>
                                    <p class="mt-2">Đang tải thông báo...</p>
                                </div>
                            </div>
                            <a class="dropdown-item text-center small text-gray-500" href="/NLNganh/view/research/notifications.php">Xem tất cả thông báo</a>
                        </div>
                    </li>

                    <div class="topbar-divider d-none d-sm-block"></div>

                    <!-- Nav Item - User Information -->
                    <li class="nav-item dropdown no-arrow">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <span class="mr-2 d-none d-lg-inline text-gray-600 small">
                                <?php 
                                if (isset($manager_info)) {
                                    echo htmlspecialchars($manager_info['QL_HO'] . ' ' . $manager_info['QL_TEN']);
                                } else {
                                    echo 'Quản lý nghiên cứu';
                                }
                                ?>
                            </span>
                            <i class="fas fa-user-circle fa-fw fa-lg"></i>
                        </a>
                        <!-- Dropdown - User Information -->
                        <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                            aria-labelledby="userDropdown">
                            <a class="dropdown-item" href="/NLNganh/view/research/manage_profile.php">
                                <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                                Hồ sơ cá nhân
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="/NLNganh/logout.php" data-toggle="modal" data-target="#logoutModal">
                                <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                Đăng xuất
                            </a>
                        </div>
                    </li>
                </ul>
            </nav>
            <!-- End of Topbar -->

            <!-- Page content starts here -->
            <div class="container-fluid">
