/**
 * Student List Management for Research Reports
 * 
 * This script handles loading and filtering the student list table
 * in the Research Reports page.
 */

// Current page for pagination
let currentPage = 1;

$(document).ready(function() {
    // Load classes when department and school year change
    $('#department, #school_year').change(function() {
        loadClasses();
    });

    // Filter student list when button is clicked
    $('#filterStudentList').click(function() {
        loadStudents(1);
    });

    // Handle pagination click
    $(document).on('click', '.page-link', function(e) {
        e.preventDefault();
        if ($(this).parent().hasClass('disabled')) {
            return;
        }
        
        currentPage = parseInt($(this).data('page'));
        loadStudents(currentPage);
    });

    // Export button handler
    $('#exportStudentListTable').click(function() {
        exportStudentList();
    });

    // Initial load if filters have values (e.g., from URL parameters)
    if ($('#department').val() || $('#school_year').val() || $('#class').val()) {
        loadStudents(1);
    }
});

/**
 * Loads class list based on selected department and school year
 */
function loadClasses() {
    const department = $('#department').val();
    const schoolYear = $('#school_year').val();
    
    // Clear current options
    $('#class').html('<option value="">Tất cả lớp</option>');
    
    if (!department && !schoolYear) {
        return;
    }
    
    // Show loading indicator
    $('#class').prop('disabled', true);
    
    // AJAX request to get classes
    $.ajax({
        url: '/NLNganh/api/get_classes.php',
        method: 'GET',
        data: {
            department: department,
            school_year: schoolYear
        },
        dataType: 'json',
        success: function(response) {
            $('#class').prop('disabled', false);
            
            if (response.success && response.data && response.data.length > 0) {
                // Add classes to dropdown
                response.data.forEach(function(classItem) {
                    $('#class').append(`<option value="${classItem.id}">${classItem.name}</option>`);
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading classes:', error);
            $('#class').prop('disabled', false);
        }
    });
}

/**
 * Loads student list based on selected filters
 * @param {number} page - Page number to load
 */
function loadStudents(page = 1) {
    const department = $('#department').val();
    const schoolYear = $('#school_year').val();
    const classId = $('#class').val();
    const researchStatus = $('#research_status').val();
    
    // Show loading indicator
    $('#studentListLoading').show();
    $('#studentListTable tbody').html('');
    $('#studentCount').text('Đang tải dữ liệu...');
    $('#studentPagination').html('');
    
    // AJAX request to get student list
    $.ajax({
        url: '/NLNganh/api/get_student_list.php',
        method: 'GET',
        data: {
            department: department,
            school_year: schoolYear,
            class: classId,
            research_status: researchStatus,
            page: page,
            limit: 20
        },
        dataType: 'json',
        success: function(response) {
            // Hide loading indicator
            $('#studentListLoading').hide();
            
            if (response.success && response.data) {
                // Clear table
                $('#studentListTable tbody').html('');
                
                if (response.data.length === 0) {
                    // No results
                    $('#studentListTable tbody').html('<tr><td colspan="7" class="text-center">Không tìm thấy sinh viên nào phù hợp với các tiêu chí lọc</td></tr>');
                    $('#studentCount').text('Hiển thị 0 sinh viên');
                    return;
                }
                
                // Add students to table
                response.data.forEach(function(student) {
                    const row = `<tr>
                        <td>${student.index}</td>
                        <td>${student.id}</td>
                        <td>${student.name}</td>
                        <td>${student.class}</td>
                        <td>${student.department}</td>
                        <td><span class="${student.status_class}">${student.status}</span></td>
                        <td>${student.project_count}</td>
                    </tr>`;
                    
                    $('#studentListTable tbody').append(row);
                });
                
                // Update student count
                $('#studentCount').text(`Hiển thị ${response.data.length} sinh viên trên tổng số ${response.pagination.total}`);
                
                // Create pagination
                if (response.pagination.total_pages > 1) {
                    createPagination(response.pagination);
                }
            } else {
                // Error handling
                console.error('Error loading student list:', response.message || 'Unknown error');
                $('#studentListTable tbody').html('<tr><td colspan="7" class="text-center text-danger">Có lỗi xảy ra khi tải danh sách sinh viên</td></tr>');
                $('#studentCount').text('Hiển thị 0 sinh viên');
            }
        },
        error: function(xhr, status, error) {
            // Hide loading indicator and show error
            $('#studentListLoading').hide();
            console.error('AJAX error loading student list:', error);
            $('#studentListTable tbody').html('<tr><td colspan="7" class="text-center text-danger">Có lỗi xảy ra khi tải danh sách sinh viên</td></tr>');
            $('#studentCount').text('Hiển thị 0 sinh viên');
        }
    });
}

/**
 * Creates pagination controls
 * @param {Object} pagination - Pagination information
 */
function createPagination(pagination) {
    const $pagination = $('#studentPagination');
    $pagination.html('');
    
    // Previous button
    $pagination.append(`
        <li class="page-item ${pagination.current_page === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${pagination.current_page - 1}" aria-label="Previous">
                <span aria-hidden="true">&laquo;</span>
            </a>
        </li>
    `);
    
    // Page numbers
    let startPage = Math.max(1, pagination.current_page - 2);
    let endPage = Math.min(pagination.total_pages, pagination.current_page + 2);
    
    if (startPage > 1) {
        $pagination.append('<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>');
        if (startPage > 2) {
            $pagination.append('<li class="page-item disabled"><span class="page-link">...</span></li>');
        }
    }
    
    for (let i = startPage; i <= endPage; i++) {
        $pagination.append(`
            <li class="page-item ${i === pagination.current_page ? 'active' : ''}">
                <a class="page-link" href="#" data-page="${i}">${i}</a>
            </li>
        `);
    }
    
    if (endPage < pagination.total_pages) {
        if (endPage < pagination.total_pages - 1) {
            $pagination.append('<li class="page-item disabled"><span class="page-link">...</span></li>');
        }
        $pagination.append(`<li class="page-item"><a class="page-link" href="#" data-page="${pagination.total_pages}">${pagination.total_pages}</a></li>`);
    }
    
    // Next button
    $pagination.append(`
        <li class="page-item ${pagination.current_page === pagination.total_pages ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${pagination.current_page + 1}" aria-label="Next">
                <span aria-hidden="true">&raquo;</span>
            </a>
        </li>
    `);
}

/**
 * Export student list to Excel
 */
function exportStudentList() {
    const department = $('#department').val();
    const schoolYear = $('#school_year').val();
    const classId = $('#class').val();
    const researchStatus = $('#research_status').val();
    
    // Change button text to loading
    const $btn = $('#exportStudentListTable');
    const originalText = $btn.html();
    $btn.html('<i class="fas fa-spinner fa-spin mr-2"></i> Đang xuất...');
    
    // Build URL with query parameters
    let url = '/NLNganh/view/research/export_student_list.php?';
    if (department) url += `department=${encodeURIComponent(department)}&`;
    if (schoolYear) url += `school_year=${encodeURIComponent(schoolYear)}&`;
    if (classId) url += `class=${encodeURIComponent(classId)}&`;
    if (researchStatus) url += `research_status=${encodeURIComponent(researchStatus)}&`;
    
    // Open in new tab
    window.open(url, '_blank');
    
    // Restore button after 1 second
    setTimeout(() => {
        $btn.html(originalText);
    }, 1000);
}
