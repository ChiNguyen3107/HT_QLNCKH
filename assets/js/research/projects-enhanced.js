/*
 * Enhanced Projects Management JavaScript
 * Advanced functionality for research project management page
 */

$(document).ready(function() {
    
    // Initialize enhanced projects functionality
    initializeProjectsEnhancements();
    
    function initializeProjectsEnhancements() {
        // Animation on scroll
        initScrollAnimations();
        
        // Advanced DataTables
        initAdvancedDataTables();
        
        // Export and print functionality
        initExportFunctions();
        
        // Filter enhancements
        initFilterEnhancements();
        
        // Enhanced interactions
        initEnhancedInteractions();
        
        // Tooltips and popovers
        initTooltipsAndPopovers();
        
        // Keyboard shortcuts
        initKeyboardShortcuts();
        
        // Auto-save preferences
        initPreferences();
        
        console.log('Projects page enhancements initialized');
    }
    
    // Scroll animations
    function initScrollAnimations() {
        function animateOnScroll() {
            $('.animate-on-scroll').each(function() {
                const elementTop = $(this).offset().top;
                const elementHeight = $(this).outerHeight();
                const windowHeight = $(window).height();
                const scrollY = window.scrollY;
                
                const delay = parseInt($(this).data('delay')) || 0;
                const animation = $(this).data('animation') || 'fadeInUp';
                
                if (elementTop < (scrollY + windowHeight - elementHeight / 2)) {
                    setTimeout(() => {
                        $(this).addClass('visible').addClass(animation);
                    }, delay);
                }
            });
            
            // Animate table rows
            $('.animate-row').each(function() {
                const elementTop = $(this).offset().top;
                const windowHeight = $(window).height();
                const scrollY = window.scrollY;
                const delay = parseInt($(this).data('delay')) || 0;
                
                if (elementTop < (scrollY + windowHeight - 50)) {
                    setTimeout(() => {
                        $(this).addClass('visible');
                    }, delay);
                }
            });
        }
        
        // Initial animation check
        setTimeout(animateOnScroll, 100);
        
        // Animation on scroll
        $(window).on('scroll', debounce(animateOnScroll, 100));
    }
    
    // Advanced DataTables functionality
    function initAdvancedDataTables() {
        const projectTable = document.getElementById('projectsTable');
        if (projectTable && $.fn.DataTable) {
            try {
                const table = $('#projectsTable').DataTable({
                    responsive: true,
                    language: { 
                        url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Vietnamese.json' 
                    },
                    pageLength: getStoredPreference('pageLength', 10),
                    lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "Tất cả"]],
                    dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                         "<'row'<'col-sm-12'tr>>" +
                         "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
                    columnDefs: [
                        { targets: 'no-sort', orderable: false },
                        { targets: [0], width: '10%' },
                        { targets: [1], width: '25%' },
                        { targets: [7], width: '15%' }
                    ],
                    order: [[6, 'desc']], // Sort by date created
                    stateSave: true, // Save table state
                    initComplete: function() {
                        addCustomStyling();
                        addColumnFilters(this.api());
                    },
                    drawCallback: function() {
                        // Re-initialize tooltips after table redraw
                        initTooltipsAndPopovers();
                    }
                });
                
                // Save page length preference
                table.on('length.dt', function(e, settings, len) {
                    storePreference('pageLength', len);
                });
                
            } catch (error) {
                console.error("Error initializing DataTables:", error);
                fallbackTableEnhancements();
            }
        } else {
            fallbackTableEnhancements();
        }
    }
    
    // Export and print functionality
    function initExportFunctions() {
        // Enhanced export to Excel
        $('#exportBtn').on('click', function() {
            exportToExcel();
        });
        
        // Print functionality
        $('#printBtn').on('click', function() {
            printTable();
        });
        
        // Add export menu
        addExportMenu();
    }
    
    // Filter enhancements
    function initFilterEnhancements() {
        // Auto-submit on filter change with debounce
        $('#filterForm select, #filterForm input').on('change input', debounce(function() {
            autoSubmitFilters();
        }, 500));
        
        // Clear filters shortcut
        $('#clearFiltersBtn').on('click', function() {
            clearAllFilters();
        });
        
        // Save filter state
        saveFilterState();
        
        // Restore filter state
        restoreFilterState();
        
        // Quick filter buttons
        addQuickFilterButtons();
    }
    
    // Enhanced interactions
    function initEnhancedInteractions() {
        // Enhanced hover effects for table rows
        $('.table tbody tr').hover(
            function() {
                $(this).addClass('table-hover-effect');
                highlightRelatedData($(this));
            },
            function() {
                $(this).removeClass('table-hover-effect');
                clearHighlights();
            }
        );
        
        // Button click effects
        $('.btn').on('click', function() {
            addClickEffect($(this));
        });
        
        // Enhanced project link interactions
        $('.project-link').on('click', function(e) {
            e.preventDefault();
            const url = $(this).attr('href');
            openProjectInModal(url);
        });
        
        // Bulk actions
        initBulkActions();
        
        // Context menu for table rows
        initContextMenu();
    }
    
    // Tooltips and popovers
    function initTooltipsAndPopovers() {
        // Enhanced tooltips
        $('[data-toggle="tooltip"]').tooltip({
            trigger: 'hover',
            delay: { show: 500, hide: 100 },
            html: true,
            placement: 'auto'
        });
        
        // Project status popovers with details
        $('.project-status').popover({
            trigger: 'hover',
            placement: 'top',
            html: true,
            content: function() {
                return getStatusDetails($(this).text().trim());
            }
        });
        
        // Faculty badge popovers
        $('.badge-secondary').popover({
            trigger: 'hover',
            placement: 'top',
            html: true,
            content: function() {
                return getFacultyDetails($(this).text().trim());
            }
        });
    }
    
    // Keyboard shortcuts
    function initKeyboardShortcuts() {
        $(document).on('keydown', function(e) {
            // Ctrl/Cmd + F for search focus
            if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                e.preventDefault();
                $('#search').focus();
            }
            
            // Ctrl/Cmd + E for export
            if ((e.ctrlKey || e.metaKey) && e.key === 'e') {
                e.preventDefault();
                exportToExcel();
            }
            
            // Ctrl/Cmd + P for print
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                printTable();
            }
            
            // Ctrl/Cmd + R for refresh/reset filters
            if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
                e.preventDefault();
                clearAllFilters();
            }
            
            // Escape to clear search
            if (e.key === 'Escape') {
                if ($('#search').is(':focus')) {
                    $('#search').val('').blur();
                    autoSubmitFilters();
                }
            }
        });
    }
    
    // Auto-save preferences
    function initPreferences() {
        // Save view preferences
        $('#projectsTable').on('column-visibility.dt', function(e, settings, column, state) {
            storePreference('columnVisibility', getColumnVisibility());
        });
        
        // Restore column visibility
        const savedVisibility = getStoredPreference('columnVisibility');
        if (savedVisibility) {
            restoreColumnVisibility(savedVisibility);
        }
    }
    
    // Helper functions
    function addCustomStyling() {
        // Add custom styling to DataTables elements
        $('.dataTables_filter input').addClass('form-control').attr('placeholder', 'Tìm kiếm trong bảng...');
        $('.dataTables_length select').addClass('form-control');
        
        // Add icons to DataTables controls
        $('.dataTables_filter label').prepend('<i class="fas fa-search mr-2"></i>');
        $('.dataTables_length label').prepend('<i class="fas fa-list mr-2"></i>');
    }
    
    function addColumnFilters(table) {
        // Add individual column filters
        $('#projectsTable thead tr').clone(true).appendTo('#projectsTable thead');
        $('#projectsTable thead tr:eq(1) th').each(function(i) {
            if (!$(this).hasClass('no-sort')) {
                const title = $(this).text();
                $(this).html(`<input type="text" class="form-control form-control-sm" placeholder="Lọc ${title}" />`);
                
                $('input', this).on('keyup change', function() {
                    if (table.column(i).search() !== this.value) {
                        table.column(i).search(this.value).draw();
                    }
                });
            } else {
                $(this).html('');
            }
        });
    }
    
    function exportToExcel() {
        showLoading('Đang xuất file...');
        
        setTimeout(() => {
            const rows = [];
            const headers = [];
            
            // Get headers (excluding action column)
            $('#projectsTable thead tr:first th').each(function(index) {
                if (index < 7) {
                    const headerText = $(this).text().trim().replace(/\s+/g, ' ');
                    headers.push(headerText.replace(/\n/g, ' '));
                }
            });
            rows.push(headers);
            
            // Get data rows
            $('#projectsTable tbody tr').each(function() {
                if (!$(this).find('td').hasClass('text-center')) {
                    const row = [];
                    $(this).find('td').each(function(index) {
                        if (index < 7) {
                            let cellText = $(this).text().trim().replace(/\s+/g, ' ');
                            // Clean up status and badge text
                            cellText = cellText.replace(/\n/g, ' ').replace(/\t/g, ' ');
                            row.push(cellText);
                        }
                    });
                    if (row.length > 0) {
                        rows.push(row);
                    }
                }
            });
            
            // Create CSV content with proper encoding
            let csvContent = "data:text/csv;charset=utf-8,\uFEFF";
            rows.forEach(rowArray => {
                const row = rowArray.map(field => {
                    const cleanField = (field || '').toString().replace(/"/g, '""');
                    return `"${cleanField}"`;
                }).join(",");
                csvContent += row + "\r\n";
            });
            
            // Download file
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", `danh_sach_de_tai_${new Date().toISOString().slice(0,10)}.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            hideLoading();
            showToast('Xuất file Excel thành công!', 'success');
        }, 1000);
    }
    
    function printTable() {
        const printContent = createPrintableTable();
        const printWindow = window.open('', '_blank');
        
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
                <head>
                    <title>Danh sách đề tài nghiên cứu</title>
                    <meta charset="utf-8">
                    <style>
                        body { 
                            font-family: Arial, sans-serif; 
                            margin: 20px; 
                            font-size: 12px;
                        }
                        .header { 
                            text-align: center; 
                            margin-bottom: 30px;
                            border-bottom: 2px solid #333;
                            padding-bottom: 20px;
                        }
                        .header h1 { 
                            color: #333; 
                            margin: 0 0 10px 0;
                            font-size: 24px;
                        }
                        .header .info { 
                            color: #666; 
                            font-size: 14px;
                        }
                        table { 
                            width: 100%; 
                            border-collapse: collapse; 
                            margin-top: 20px; 
                        }
                        th, td { 
                            border: 1px solid #ddd; 
                            padding: 8px; 
                            text-align: left; 
                            vertical-align: top;
                        }
                        th { 
                            background-color: #f8f9fa; 
                            font-weight: bold; 
                            font-size: 11px;
                            text-transform: uppercase;
                        }
                        td { 
                            font-size: 10px; 
                        }
                        .project-status { 
                            padding: 3px 6px; 
                            border-radius: 3px; 
                            font-size: 9px;
                            font-weight: bold;
                            text-transform: uppercase;
                        }
                        .status-pending { background-color: #fff3cd; color: #856404; }
                        .status-progress { background-color: #d1ecf1; color: #0c5460; }
                        .status-completed { background-color: #d4edda; color: #155724; }
                        .status-rejected { background-color: #f8d7da; color: #721c24; }
                        .footer {
                            margin-top: 30px;
                            border-top: 1px solid #ddd;
                            padding-top: 20px;
                            text-align: center;
                            font-size: 10px;
                            color: #666;
                        }
                        @media print {
                            body { margin: 0; }
                            .header { page-break-inside: avoid; }
                            table { page-break-inside: avoid; }
                            tr { page-break-inside: avoid; }
                        }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h1>DANH SÁCH ĐỀ TÀI NGHIÊN CỨU KHOA HỌC</h1>
                        <div class="info">
                            <p>Ngày in: ${new Date().toLocaleDateString('vi-VN', {
                                weekday: 'long',
                                year: 'numeric', 
                                month: 'long', 
                                day: 'numeric'
                            })}</p>
                            <p>Tổng số đề tài: ${$('#projectsTable tbody tr').length - ($('.empty-state').length > 0 ? 1 : 0)}</p>
                        </div>
                    </div>
                    ${printContent}
                    <div class="footer">
                        <p>Hệ thống Quản lý Nghiên cứu Khoa học - ${new Date().getFullYear()}</p>
                    </div>
                </body>
            </html>
        `);
        
        printWindow.document.close();
        
        // Wait for content to load then print
        printWindow.onload = function() {
            printWindow.focus();
            printWindow.print();
        };
    }
    
    function createPrintableTable() {
        const table = $('#projectsTable').clone();
        
        // Remove action column
        table.find('th:last, td:last').remove();
        
        // Clean up content for printing
        table.find('.project-link').each(function() {
            $(this).replaceWith($(this).text());
        });
        
        table.find('.badge').each(function() {
            $(this).replaceWith(`<span class="print-badge">${$(this).text()}</span>`);
        });
        
        return table[0].outerHTML;
    }
    
    function autoSubmitFilters() {
        // Show loading indicator
        showFilterLoading();
        
        // Submit form after a short delay
        setTimeout(() => {
            $('#filterForm').submit();
        }, 300);
    }
    
    function clearAllFilters() {
        $('#filterForm')[0].reset();
        window.location.href = 'manage_projects.php';
    }
    
    function saveFilterState() {
        $('#filterForm').on('submit', function() {
            const formData = $(this).serialize();
            storePreference('filterState', formData);
        });
    }
    
    function restoreFilterState() {
        const savedFilters = getStoredPreference('filterState');
        if (savedFilters && window.location.search === '') {
            // Restore filters if no current filters
            const params = new URLSearchParams(savedFilters);
            params.forEach((value, key) => {
                $(`[name="${key}"]`).val(value);
            });
        }
    }
    
    function addQuickFilterButtons() {
        const quickFilters = `
            <div class="quick-filters mt-3">
                <h6 class="font-weight-bold text-dark mb-2">
                    <i class="fas fa-bolt mr-1"></i>Lọc nhanh:
                </h6>
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-outline-primary btn-sm quick-filter" data-filter="status" data-value="Chờ phê duyệt">
                        <i class="fas fa-clock mr-1"></i>Chờ duyệt
                    </button>
                    <button type="button" class="btn btn-outline-info btn-sm quick-filter" data-filter="status" data-value="Đang tiến hành">
                        <i class="fas fa-play mr-1"></i>Đang tiến hành
                    </button>
                    <button type="button" class="btn btn-outline-success btn-sm quick-filter" data-filter="status" data-value="Đã hoàn thành">
                        <i class="fas fa-check mr-1"></i>Hoàn thành
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="clearFiltersBtn">
                        <i class="fas fa-times mr-1"></i>Xóa bộ lọc
                    </button>
                </div>
            </div>
        `;
        
        $('.filter-card .card-body').append(quickFilters);
        
        // Handle quick filter clicks
        $('.quick-filter').on('click', function() {
            const filter = $(this).data('filter');
            const value = $(this).data('value');
            
            $(`[name="${filter}"]`).val(value);
            autoSubmitFilters();
        });
    }
    
    function addExportMenu() {
        // Create dropdown for export options
        const exportMenu = `
            <div class="dropdown">
                <button class="btn btn-success btn-sm dropdown-toggle" type="button" data-toggle="dropdown">
                    <i class="fas fa-download mr-1"></i>Xuất dữ liệu
                </button>
                <div class="dropdown-menu">
                    <a class="dropdown-item" href="#" id="exportExcelBtn">
                        <i class="fas fa-file-excel mr-2"></i>Xuất Excel
                    </a>
                    <a class="dropdown-item" href="#" id="exportPdfBtn">
                        <i class="fas fa-file-pdf mr-2"></i>Xuất PDF
                    </a>
                    <a class="dropdown-item" href="#" id="exportCsvBtn">
                        <i class="fas fa-file-csv mr-2"></i>Xuất CSV
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="#" id="printBtn">
                        <i class="fas fa-print mr-2"></i>In danh sách
                    </a>
                </div>
            </div>
        `;
        
        // Replace single export button with dropdown
        $('#exportBtn').replaceWith(exportMenu);
        
        // Handle export options
        $('#exportExcelBtn, #exportCsvBtn').on('click', function(e) {
            e.preventDefault();
            exportToExcel();
        });
        
        $('#exportPdfBtn').on('click', function(e) {
            e.preventDefault();
            showToast('Tính năng xuất PDF đang được phát triển', 'info');
        });
    }
    
    function initBulkActions() {
        // Add checkboxes to table
        addBulkCheckboxes();
        
        // Bulk action bar
        addBulkActionBar();
        
        // Handle checkbox changes
        handleBulkSelection();
    }
    
    function addBulkCheckboxes() {
        // Add master checkbox to header
        const masterCheckbox = '<input type="checkbox" id="selectAll" class="bulk-checkbox">';
        $('#projectsTable thead tr:first th:first').prepend(masterCheckbox);
        
        // Add individual checkboxes to rows
        $('#projectsTable tbody tr').each(function() {
            if (!$(this).find('.empty-state').length) {
                const projectId = $(this).find('td:first').text().trim();
                const checkbox = `<input type="checkbox" class="bulk-checkbox-item" value="${projectId}">`;
                $(this).find('td:first').prepend(checkbox);
            }
        });
    }
    
    function addBulkActionBar() {
        const bulkBar = `
            <div id="bulkActionBar" class="bulk-action-bar" style="display: none;">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="selected-count">0 đề tài được chọn</span>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-success bulk-action" data-action="approve">
                            <i class="fas fa-check mr-1"></i>Phê duyệt
                        </button>
                        <button class="btn btn-sm btn-warning bulk-action" data-action="edit">
                            <i class="fas fa-edit mr-1"></i>Chỉnh sửa hàng loạt
                        </button>
                        <button class="btn btn-sm btn-danger bulk-action" data-action="delete">
                            <i class="fas fa-trash mr-1"></i>Xóa
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        $('.projects-table-card .card-body').prepend(bulkBar);
    }
    
    function handleBulkSelection() {
        // Master checkbox
        $('#selectAll').on('change', function() {
            const isChecked = $(this).is(':checked');
            $('.bulk-checkbox-item').prop('checked', isChecked);
            updateBulkActionBar();
        });
        
        // Individual checkboxes
        $(document).on('change', '.bulk-checkbox-item', function() {
            updateBulkActionBar();
        });
        
        // Bulk actions
        $('.bulk-action').on('click', function() {
            const action = $(this).data('action');
            const selectedItems = $('.bulk-checkbox-item:checked').map(function() {
                return $(this).val();
            }).get();
            
            if (selectedItems.length === 0) {
                showToast('Vui lòng chọn ít nhất một đề tài', 'warning');
                return;
            }
            
            handleBulkAction(action, selectedItems);
        });
    }
    
    function updateBulkActionBar() {
        const selectedCount = $('.bulk-checkbox-item:checked').length;
        const totalCount = $('.bulk-checkbox-item').length;
        
        if (selectedCount > 0) {
            $('#bulkActionBar').show();
            $('.selected-count').text(`${selectedCount} đề tài được chọn`);
        } else {
            $('#bulkActionBar').hide();
        }
        
        // Update master checkbox state
        if (selectedCount === 0) {
            $('#selectAll').prop('indeterminate', false).prop('checked', false);
        } else if (selectedCount === totalCount) {
            $('#selectAll').prop('indeterminate', false).prop('checked', true);
        } else {
            $('#selectAll').prop('indeterminate', true);
        }
    }
    
    function handleBulkAction(action, selectedItems) {
        switch (action) {
            case 'approve':
                if (confirm(`Bạn có chắc chắn muốn phê duyệt ${selectedItems.length} đề tài?`)) {
                    // Implementation would go here
                    showToast('Tính năng phê duyệt hàng loạt đang được phát triển', 'info');
                }
                break;
            case 'edit':
                showToast('Tính năng chỉnh sửa hàng loạt đang được phát triển', 'info');
                break;
            case 'delete':
                if (confirm(`Bạn có chắc chắn muốn xóa ${selectedItems.length} đề tài?`)) {
                    // Implementation would go here
                    showToast('Tính năng xóa hàng loạt đang được phát triển', 'info');
                }
                break;
        }
    }
    
    function initContextMenu() {
        // Add context menu for table rows
        $('#projectsTable tbody tr').on('contextmenu', function(e) {
            e.preventDefault();
            
            if ($(this).find('.empty-state').length) return;
            
            const projectId = $(this).find('td:first').text().trim();
            showContextMenu(e.pageX, e.pageY, projectId);
        });
        
        // Hide context menu on click outside
        $(document).on('click', function() {
            $('.context-menu').remove();
        });
    }
    
    function showContextMenu(x, y, projectId) {
        $('.context-menu').remove();
        
        const menu = `
            <div class="context-menu" style="position: absolute; top: ${y}px; left: ${x}px; z-index: 1000;">
                <div class="dropdown-menu show">
                    <a class="dropdown-item" href="view_project.php?id=${projectId}">
                        <i class="fas fa-eye mr-2"></i>Xem chi tiết
                    </a>
                    <a class="dropdown-item" href="edit_project.php?id=${projectId}">
                        <i class="fas fa-edit mr-2"></i>Chỉnh sửa
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item text-success" href="approve_project.php?id=${projectId}">
                        <i class="fas fa-check mr-2"></i>Phê duyệt
                    </a>
                    <a class="dropdown-item text-danger" href="#" onclick="confirmDelete('${projectId}')">
                        <i class="fas fa-trash mr-2"></i>Xóa
                    </a>
                </div>
            </div>
        `;
        
        $('body').append(menu);
    }
    
    // Utility functions
    function showLoading(message = 'Đang xử lý...') {
        const loading = `
            <div id="loadingOverlay" class="loading-overlay">
                <div class="loading-content">
                    <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
                    <p>${message}</p>
                </div>
            </div>
        `;
        $('body').append(loading);
    }
    
    function hideLoading() {
        $('#loadingOverlay').fadeOut(() => {
            $('#loadingOverlay').remove();
        });
    }
    
    function showFilterLoading() {
        $('#filterForm .btn[type="submit"]').addClass('loading').prop('disabled', true);
    }
    
    function showToast(message, type = 'info') {
        const toastId = 'toast-' + Date.now();
        const toast = $(`
            <div id="${toastId}" class="toast-notification toast-${type} position-fixed" style="top: 20px; right: 20px; z-index: 9999; opacity: 0;">
                <div class="alert alert-${type === 'success' ? 'success' : type === 'warning' ? 'warning' : 'info'} alert-dismissible mb-0">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle'} mr-2"></i>
                    ${message}
                    <button type="button" class="close" onclick="$('#${toastId}').fadeOut(() => $('#${toastId}').remove());">
                        <span>&times;</span>
                    </button>
                </div>
            </div>
        `);
        
        $('body').append(toast);
        toast.animate({opacity: 1}, 300);
        
        setTimeout(() => {
            toast.fadeOut(() => toast.remove());
        }, 5000);
    }
    
    function addClickEffect(element) {
        element.addClass('btn-clicked');
        setTimeout(() => {
            element.removeClass('btn-clicked');
        }, 200);
    }
    
    function highlightRelatedData(row) {
        // Highlight related data in other rows
        const faculty = row.find('td:eq(3)').text().trim();
        const status = row.find('.project-status').text().trim();
        
        $(`td:contains("${faculty}")`).addClass('highlight-related');
        $(`.project-status:contains("${status}")`).addClass('highlight-related');
    }
    
    function clearHighlights() {
        $('.highlight-related').removeClass('highlight-related');
    }
    
    function getStatusDetails(status) {
        const statusInfo = {
            'Chờ phê duyệt': 'Đề tài đang chờ được xem xét và phê duyệt bởi ban quản lý',
            'Đang tiến hành': 'Đề tài đã được phê duyệt và đang trong quá trình thực hiện',
            'Đã hoàn thành': 'Đề tài đã hoàn thành và nộp báo cáo kết quả',
            'Đã từ chối': 'Đề tài không được phê duyệt hoặc bị từ chối'
        };
        
        return statusInfo[status] || 'Thông tin trạng thái không có sẵn';
    }
    
    function getFacultyDetails(faculty) {
        // This could fetch real faculty details from an API
        return `<strong>${faculty}</strong><br>Click để xem chi tiết khoa/đơn vị`;
    }
    
    function fallbackTableEnhancements() {
        // Fallback enhancements when DataTables is not available
        console.warn('DataTables not available, using fallback enhancements');
        
        // Simple search functionality
        $('#search').on('input', debounce(function() {
            const searchTerm = $(this).val().toLowerCase();
            $('#projectsTable tbody tr').each(function() {
                const text = $(this).text().toLowerCase();
                $(this).toggle(text.includes(searchTerm));
            });
        }, 300));
    }
    
    function openProjectInModal(url) {
        // Implementation for opening project details in modal
        showToast('Tính năng xem nhanh đang được phát triển', 'info');
    }
    
    function storePreference(key, value) {
        try {
            localStorage.setItem(`projectsPage_${key}`, JSON.stringify(value));
        } catch (e) {
            console.warn('Cannot store preference:', e);
        }
    }
    
    function getStoredPreference(key, defaultValue = null) {
        try {
            const stored = localStorage.getItem(`projectsPage_${key}`);
            return stored ? JSON.parse(stored) : defaultValue;
        } catch (e) {
            console.warn('Cannot get stored preference:', e);
            return defaultValue;
        }
    }
    
    function getColumnVisibility() {
        const visibility = {};
        $('#projectsTable thead th').each(function(index) {
            visibility[index] = $(this).is(':visible');
        });
        return visibility;
    }
    
    function restoreColumnVisibility(visibility) {
        Object.keys(visibility).forEach(index => {
            if (!visibility[index]) {
                $(`#projectsTable th:eq(${index}), #projectsTable td:nth-child(${parseInt(index) + 1})`).hide();
            }
        });
    }
    
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
});

// Global functions for external use
window.ProjectsManager = {
    exportToExcel: function() {
        $('#exportBtn').click();
    },
    
    printTable: function() {
        $('#printBtn').click();
    },
    
    clearFilters: function() {
        $('#clearFiltersBtn').click();
    },
    
    searchProjects: function(term) {
        $('#search').val(term).trigger('input');
    }
};

// CSS for loading overlay and other dynamic elements
$('<style>').text(`
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    }
    
    .loading-content {
        background: white;
        padding: 2rem;
        border-radius: 10px;
        text-align: center;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    }
    
    .bulk-action-bar {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 1rem;
        border-radius: 10px;
        margin-bottom: 1rem;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    }
    
    .highlight-related {
        background-color: rgba(102, 126, 234, 0.1) !important;
    }
    
    .context-menu .dropdown-menu {
        border: none;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        border-radius: 10px;
        padding: 0.5rem 0;
    }
    
    .context-menu .dropdown-item {
        padding: 0.5rem 1rem;
        transition: all 0.3s ease;
    }
    
    .context-menu .dropdown-item:hover {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .quick-filters {
        border-top: 1px solid #dee2e6;
        padding-top: 1rem;
    }
    
    .quick-filter.active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-color: #667eea;
        color: white;
    }
`).appendTo('head');
