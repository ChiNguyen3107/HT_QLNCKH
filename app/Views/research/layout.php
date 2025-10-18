<?php
// Template file to show the correct layout structure for research pages

// Include header
include '../../include/research_header.php';
?>
<!-- Page Wrapper -->
<div id="wrapper">
    <!-- Sidebar -->
    <?php // Sidebar được include trong research_header.php ?>
    
    <!-- Content Wrapper -->
    <div id="content-wrapper" class="d-flex flex-column">
        <!-- Main Content -->
        <div id="content">
            <!-- Topbar -->
            <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                    <i class="fa fa-bars"></i>
                </button>
                <h1 class="h3 mb-0 text-gray-800 ml-2">Tiêu đề trang</h1>
            </nav>
            
            <!-- Begin Page Content -->
            <div class="container-fluid">
                <!-- Nội dung trang -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Nội dung</h6>
                    </div>
                    <div class="card-body">
                        Đây là nội dung của trang.
                    </div>
                </div>
            </div>
            <!-- /.container-fluid -->
        </div>
        <!-- End of Main Content -->
        
        <!-- Footer sẽ được include trong research_footer.php -->
    </div>
    <!-- End of Content Wrapper -->
</div>
<!-- End of Page Wrapper -->

<?php
// Include footer
include '../../include/research_footer.php';
?>
