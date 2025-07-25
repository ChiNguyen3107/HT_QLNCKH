<?php
session_start();

// Set test research manager session
$_SESSION['user_id'] = 'QL001'; // Sử dụng ID theo định dạng trong DB
$_SESSION['username'] = 'research_manager';
$_SESSION['role'] = 'research_manager';
$_SESSION['fullname'] = 'Quản lý Nghiên cứu';

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Research Sidebar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <?php include 'include/modern_research_sidebar.php'; ?>
    
    <div class="content-wrapper">
        <div class="container-fluid p-4">
            <div class="row">
                <div class="col-12">
                    <div class="card shadow">
                        <div class="card-header bg-primary text-white">
                            <h5 class="m-0">
                                <i class="fas fa-vials mr-2"></i>
                                Test Research Manager Sidebar
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle mr-2"></i>
                                Sidebar loaded successfully!
                            </div>
                            
                            <h6>Session Information:</h6>
                            <ul>
                                <li><strong>User ID:</strong> <?php echo $_SESSION['user_id']; ?></li>
                                <li><strong>Role:</strong> <?php echo $_SESSION['role']; ?></li>
                                <li><strong>Username:</strong> <?php echo $_SESSION['username']; ?></li>
                            </ul>

                            <?php if ($manager_info): ?>
                            <h6>Manager Information:</h6>
                            <ul>
                                <li><strong>Name:</strong> <?php echo htmlspecialchars($manager_info['QL_HO'] . ' ' . $manager_info['QL_TEN']); ?></li>
                                <li><strong>Email:</strong> <?php echo htmlspecialchars($manager_info['QL_EMAIL']); ?></li>
                                <li><strong>Phone:</strong> <?php echo htmlspecialchars($manager_info['QL_SDT']); ?></li>
                            </ul>
                            <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                Manager information not found in database
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        body {
            background-color: #f8f9fc;
        }
        
        .content-wrapper {
            min-height: 100vh;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        
        .card-header {
            border-radius: 15px 15px 0 0 !important;
        }
    </style>
</body>
</html>
