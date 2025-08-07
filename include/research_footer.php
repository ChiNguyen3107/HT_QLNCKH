<?php
// filepath: d:\xampp\htdocs\NLNganh\include\research_footer.php
// Common footer file for research manager pages
// Contains standard JS includes and common functionality
?>

                </div>
                <!-- Page content ends here -->
            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; Hệ thống Quản lý Nghiên cứu Khoa học <?php echo date('Y'); ?></span>
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

    <!-- Logout Modal-->
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Xác nhận đăng xuất?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">Bạn có chắc chắn muốn kết thúc phiên làm việc hiện tại?</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Hủy</button>
                    <a class="btn btn-primary" href="/NLNganh/logout.php">Đăng xuất</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap core JavaScript-->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.4.1/jquery.easing.min.js"></script>    <!-- Custom scripts for all pages-->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/startbootstrap-sb-admin-2/4.1.3/js/sb-admin-2.min.js"></script>
    
    <!-- Icon fallback fix JS -->
    <script src="/NLNganh/assets/js/research/icon-fix.js"></script>
      <!-- Page level plugins -->
    <script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.24/js/dataTables.bootstrap4.min.js"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js"></script>    <!-- DataTable Helper -->
    <script src="/NLNganh/assets/js/research/datatable-helper.js"></script>
    
    <!-- Simple Sidebar JavaScript -->
    <script src="/NLNganh/assets/js/research/simple-sidebar.js"></script>
    
    <!-- Modern Sidebar JavaScript -->
    <script src="/NLNganh/assets/js/research/research-sidebar-unified.js"></script>
    
    <!-- Sidebar Dropdown Enhancement -->
    <script src="/NLNganh/assets/js/research/sidebar-dropdown-enhanced.js"></script>
    
    <!-- Simple Dropdown Test -->
    <script src="/NLNganh/assets/js/research/simple-dropdown-test.js"></script>
    
    <!-- Fixed Sidebar Permanent - Vô hiệu hóa thu gọn sidebar -->
    <script src="/NLNganh/assets/js/research/fixed-sidebar-permanent.js"></script>
    
    <!-- Sidebar Layout Fix - Sửa lỗi sidebar đè lên nội dung -->
    <script src="/NLNganh/assets/js/research/sidebar-layout-fix.js"></script>

    <!-- UI Handler mới cho tất cả các trang -->
    <script src="/NLNganh/assets/js/research/research-ui-handler-fixed.js"></script>

    <!-- Notification Handler -->
    <script src="/NLNganh/assets/js/research/notification-handler-fixed.js"></script>
    
    <!-- Load notifications for dropdown -->
    <script>
    // Wait for jQuery to be fully loaded
    window.addEventListener('load', function() {
        if (typeof $ !== 'undefined') {
            $(document).ready(function() {
                // Function to load notifications into dropdown
                function loadNotificationsDropdown() {
                    const container = $("#notifications-container");
                    if (container.length === 0) {
                        console.log('Notifications container not found');
                        return;
                    }
                    
                    $.ajax({
                        url: '/NLNganh/view/research/notifications.php',
                        type: 'GET',
                        data: { format: 'dropdown', limit: 5 },
                        success: function(data) {
                            container.html(data);
                        },
                        error: function() {
                            container.html('<a class="dropdown-item d-flex align-items-center" href="#">' +
                                           '<div class="mr-3"><div class="icon-circle bg-warning"><i class="fas fa-exclamation-triangle text-white"></i></div></div>' +
                                   '<div><div class="small text-gray-500">Hôm nay</div>Không thể tải thông báo. Hãy thử lại sau.</div>' +
                                   '</a>');
                        }
                    });
                }
                
                // Load notifications when dropdown is shown
                $('#alertsDropdown').on('shown.bs.dropdown', function () {
                    loadNotificationsDropdown();
                });
                
                // Initialize tooltips
                if (typeof $.fn.tooltip !== 'undefined') {
                    $('[data-toggle="tooltip"]').tooltip();
                }
                
                // Initialize popovers
                if (typeof $.fn.popover !== 'undefined') {
                    $('[data-toggle="popover"]').popover();
                }
                
                // DataTable initialization with responsive features
                if (typeof $.fn.DataTable !== 'undefined') {
                    $('.dataTable').each(function() {
                        if (!$.fn.DataTable.isDataTable(this)) {
                            $(this).DataTable({
                                responsive: true,
                                language: {
                                    url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Vietnamese.json'
                                }
                            });
                        }
                    });
                }
            });
        } else {
            console.error('jQuery not loaded in footer');
        }
    });
    </script>
    
    <!-- Page specific JS can be added here -->
    <?php if (isset($additional_js)): ?>
        <?php echo $additional_js; ?>
    <?php endif; ?>
</body>
</html>
