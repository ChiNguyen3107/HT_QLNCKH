// File: council_members.js
// JavaScript để xử lý việc chọn và quản lý thành viên hội đồng nghiệm thu

let councilMembers = [];
let availableTeachers = [];
let projectSupervisor = null; // Thông tin giảng viên hướng dẫn

// Khởi tạo khi trang load
$(document).ready(function() {
    // Load thông tin giảng viên hướng dẫn
    loadProjectSupervisor();
    
    // Load danh sách giảng viên
    loadAvailableTeachers();
    
    // Parse existing council members if any
    const existingMembers = $('#council_members').val();
    if (existingMembers) {
        try {
            councilMembers = JSON.parse(existingMembers);
            renderCouncilMembers();
        } catch (e) {
            console.log('Error parsing existing members:', e);
            // Nếu không parse được JSON, thử parse text format
            parseTextToMembers(existingMembers);
        }
    }
});

// Load thông tin giảng viên hướng dẫn của đề tài
function loadProjectSupervisor() {
    const projectId = $('input[name="project_id"]').val();
    if (!projectId) {
        console.error('Không tìm thấy project_id');
        return;
    }
    
    $.ajax({
        url: '/NLNganh/api/get_project_supervisor.php',
        method: 'GET',
        data: { project_id: projectId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                projectSupervisor = response.data;
                console.log('Loaded supervisor:', projectSupervisor);
            } else {
                console.error('Error loading supervisor:', response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error loading supervisor:', error);
        }
    });
}

// Load danh sách giảng viên từ API
function loadAvailableTeachers() {
    $.ajax({
        url: '/NLNganh/api/get_teachers.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                availableTeachers = response.data;
                console.log('Loaded teachers:', availableTeachers.length);
            } else {
                console.error('Error loading teachers:', response.message);
                showMessage('Lỗi tải danh sách giảng viên: ' + response.message, 'danger');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error loading teachers:', error);
            showMessage('Lỗi kết nối khi tải danh sách giảng viên', 'danger');
        }
    });
}

// Parse text format thành array members
function parseTextToMembers(textMembers) {
    const lines = textMembers.split('\n');
    councilMembers = [];
    
    lines.forEach(line => {
        const parts = line.split(' - ');
        if (parts.length >= 2) {
            councilMembers.push({
                id: 'GV' + (councilMembers.length + 1).toString().padStart(3, '0'),
                name: parts[0].trim(),
                role: parts[1].trim()
            });
        }
    });
    
    renderCouncilMembers();
}

// Hiển thị modal chọn thành viên
$('#addCouncilMemberBtn').on('click', function() {
    if (availableTeachers.length === 0) {
        showMessage('Đang tải danh sách giảng viên...', 'info');
        loadAvailableTeachers();
        return;
    }
    
    showTeacherSelectionModal();
});

// Hiển thị modal chọn giảng viên
function showTeacherSelectionModal() {
    let modalHtml = `
        <div class="modal fade" id="teacherSelectionModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-users mr-2"></i>Chọn thành viên hội đồng
                        </h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-2"></i>
                            <strong>Lưu ý:</strong> Giảng viên hướng dẫn không được phép tham gia hội đồng nghiệm thu của đề tài mình hướng dẫn để đảm bảo tính khách quan.
                        </div>
                        <div class="form-group">
                            <label>Tìm kiếm giảng viên:</label>
                            <input type="text" class="form-control" id="teacherSearch" 
                                   placeholder="Nhập tên hoặc mã giảng viên...">
                        </div>
                        <div class="teachers-list" id="teachersList">
                            <!-- Danh sách giảng viên sẽ được load ở đây -->
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing modal if any
    $('#teacherSelectionModal').remove();
    
    // Add modal to body
    $('body').append(modalHtml);
    
    // Show modal
    $('#teacherSelectionModal').modal('show');
    
    // Render teachers list
    renderTeachersList();
    
    // Setup search functionality
    $('#teacherSearch').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        filterTeachers(searchTerm);
    });
}

// Hiển thị danh sách giảng viên
function renderTeachersList(filteredTeachers = null) {
    const teachers = filteredTeachers || availableTeachers;
    const teachersListHtml = teachers.map(teacher => {
        const isSelected = councilMembers.some(member => member.id === teacher.id);
        const isSupervisor = projectSupervisor && projectSupervisor.id === teacher.id;
        
        let buttonText, buttonClass, buttonDisabled, cardClass, supervisorBadge;
        
        if (isSupervisor) {
            buttonText = 'Giảng viên hướng dẫn';
            buttonClass = 'btn-warning';
            buttonDisabled = 'disabled';
            cardClass = 'border-warning';
            supervisorBadge = '<span class="badge badge-warning ml-2"><i class="fas fa-chalkboard-teacher mr-1"></i>GV hướng dẫn</span>';
        } else if (isSelected) {
            buttonText = 'Đã chọn';
            buttonClass = 'btn-secondary';
            buttonDisabled = 'disabled';
            cardClass = '';
            supervisorBadge = '';
        } else {
            buttonText = 'Chọn';
            buttonClass = 'btn-primary';
            buttonDisabled = '';
            cardClass = '';
            supervisorBadge = '';
        }
        
        return `
            <div class="card mb-2 ${cardClass}">
                <div class="card-body py-2">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h6 class="mb-1">
                                ${teacher.name}
                                ${supervisorBadge}
                            </h6>
                            <small class="text-muted">
                                <i class="fas fa-id-card mr-1"></i>${teacher.id} | 
                                <i class="fas fa-building mr-1"></i>${teacher.department || 'N/A'}
                            </small>
                            ${isSupervisor ? '<br><small class="text-warning"><i class="fas fa-exclamation-triangle mr-1"></i>Không thể tham gia hội đồng nghiệm thu</small>' : ''}
                        </div>
                        <div class="col-md-4 text-right">
                            <button type="button" class="btn btn-sm ${buttonClass} select-teacher-btn" 
                                    data-teacher-id="${teacher.id}" 
                                    data-teacher-name="${teacher.name}" ${buttonDisabled}>
                                <i class="fas fa-user-plus mr-1"></i>${buttonText}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }).join('');
    
    $('#teachersList').html(teachersListHtml || '<p class="text-center text-muted">Không tìm thấy giảng viên nào.</p>');
    
    // Bind click events
    $('.select-teacher-btn').on('click', function() {
        const teacherId = $(this).data('teacher-id');
        const teacherName = $(this).data('teacher-name');
        selectTeacher(teacherId, teacherName);
    });
}

// Lọc giảng viên theo từ khóa
function filterTeachers(searchTerm) {
    const filtered = availableTeachers.filter(teacher => 
        teacher.name.toLowerCase().includes(searchTerm) ||
        teacher.id.toLowerCase().includes(searchTerm) ||
        (teacher.department && teacher.department.toLowerCase().includes(searchTerm))
    );
    renderTeachersList(filtered);
}

// Chọn giảng viên làm thành viên hội đồng
function selectTeacher(teacherId, teacherName) {
    // Kiểm tra xem đã chọn chưa
    const existingMember = councilMembers.find(member => member.id === teacherId);
    if (existingMember) {
        showMessage('Giảng viên này đã được chọn làm thành viên hội đồng', 'warning');
        return;
    }
    
    // Kiểm tra xem có phải là giảng viên hướng dẫn không
    if (projectSupervisor && projectSupervisor.id === teacherId) {
        showMessage('Không thể thêm giảng viên hướng dẫn vào thành viên hội đồng. Giảng viên hướng dẫn không được phép tham gia hội đồng nghiệm thu của đề tài mình hướng dẫn.', 'warning');
        return;
    }
    
    // Hiển thị modal chọn vai trò
    showRoleSelectionModal(teacherId, teacherName);
}

// Hiển thị modal chọn vai trò
function showRoleSelectionModal(teacherId, teacherName) {
    const roles = ['Chủ tịch', 'Thành viên', 'Thư ký'];
    
    let roleModalHtml = `
        <div class="modal fade" id="roleSelectionModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-user-tag mr-2"></i>Chọn vai trò
                        </h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p><strong>Giảng viên:</strong> ${teacherName}</p>
                        <p><strong>Mã GV:</strong> ${teacherId}</p>
                        <div class="form-group">
                            <label>Vai trò trong hội đồng:</label>
                            <select class="form-control" id="memberRole">
                                ${roles.map(role => `<option value="${role}">${role}</option>`).join('')}
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                        <button type="button" class="btn btn-success" id="confirmAddMember">
                            <i class="fas fa-plus mr-1"></i>Thêm thành viên
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing modal if any
    $('#roleSelectionModal').remove();
    
    // Add modal to body
    $('body').append(roleModalHtml);
    
    // Show modal
    $('#roleSelectionModal').modal('show');
    
    // Handle confirm button
    $('#confirmAddMember').on('click', function() {
        const selectedRole = $('#memberRole').val();
        addCouncilMember(teacherId, teacherName, selectedRole);
        $('#roleSelectionModal').modal('hide');
        $('#teacherSelectionModal').modal('hide');
    });
}

// Thêm thành viên vào hội đồng
function addCouncilMember(teacherId, teacherName, role) {
    // Kiểm tra vai trò trùng lặp (chỉ được có 1 chủ tịch và 1 thư ký)
    if (role === 'Chủ tịch') {
        const existingChairman = councilMembers.find(member => member.role === 'Chủ tịch');
        if (existingChairman) {
            showMessage('Đã có chủ tịch hội đồng. Vui lòng xóa chủ tịch hiện tại trước khi thêm mới.', 'warning');
            return;
        }
    }
    
    if (role === 'Thư ký') {
        const existingSecretary = councilMembers.find(member => member.role === 'Thư ký');
        if (existingSecretary) {
            showMessage('Đã có thư ký hội đồng. Vui lòng xóa thư ký hiện tại trước khi thêm mới.', 'warning');
            return;
        }
    }
    
    const newMember = {
        id: teacherId,
        name: teacherName,
        role: role
    };
    
    councilMembers.push(newMember);
    renderCouncilMembers();
    updateCouncilMembersInput();
    
    showMessage(`Đã thêm ${teacherName} làm ${role} hội đồng`, 'success');
}

// Hiển thị danh sách thành viên đã chọn
function renderCouncilMembers() {
    if (councilMembers.length === 0) {
        $('#selectedCouncilMembers').html(`
            <div class="alert alert-info">
                <i class="fas fa-info-circle mr-2"></i>
                Chưa có thành viên nào được chọn. Nhấn nút "Thêm thành viên hội đồng" để bắt đầu.
            </div>
        `);
        return;
    }
    
    const membersHtml = councilMembers.map((member, index) => {
        const roleClass = member.role === 'Chủ tịch' ? 'badge-danger' : 
                         (member.role === 'Thư ký' ? 'badge-info' : 'badge-primary');
        
        return `
            <div class="card mb-2 council-member-card">
                <div class="card-body py-2">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h6 class="mb-1">${member.name}</h6>
                            <small class="text-muted">
                                <i class="fas fa-id-card mr-1"></i>${member.id}
                            </small>
                        </div>
                        <div class="col-md-4 text-right">
                            <span class="badge ${roleClass} mr-2">${member.role}</span>
                            <button type="button" class="btn btn-sm btn-outline-danger remove-member-btn" 
                                    data-member-index="${index}" title="Xóa thành viên">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }).join('');
    
    $('#selectedCouncilMembers').html(`
        <div class="alert alert-success">
            <i class="fas fa-users mr-2"></i>
            <strong>Đã chọn ${councilMembers.length} thành viên hội đồng:</strong>
        </div>
        ${membersHtml}
    `);
    
    // Bind remove events
    $('.remove-member-btn').on('click', function() {
        const memberIndex = $(this).data('member-index');
        removeMember(memberIndex);
    });
}

// Xóa thành viên khỏi hội đồng
function removeMember(index) {
    if (confirm('Bạn có chắc chắn muốn xóa thành viên này khỏi hội đồng?')) {
        const removedMember = councilMembers[index];
        councilMembers.splice(index, 1);
        renderCouncilMembers();
        updateCouncilMembersInput();
        
        showMessage(`Đã xóa ${removedMember.name} khỏi hội đồng`, 'info');
    }
}

// Cập nhật input hidden
function updateCouncilMembersInput() {
    const jsonData = JSON.stringify(councilMembers);
    $('#council_members').val(jsonData);
}

// Hiển thị thông báo
function showMessage(message, type = 'info') {
    const alertClass = `alert-${type}`;
    const iconClass = type === 'success' ? 'fas fa-check-circle' : 
                     type === 'danger' ? 'fas fa-exclamation-triangle' : 
                     type === 'warning' ? 'fas fa-exclamation-circle' : 'fas fa-info-circle';
    
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            <i class="${iconClass} mr-2"></i>${message}
            <button type="button" class="close" data-dismiss="alert">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    `;
    
    // Remove existing alerts
    $('.alert-dismissible').remove();
    
    // Add new alert at the top of the form
    $('.decision-update-form, .report-update-form').prepend(alertHtml);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        $('.alert-dismissible').fadeOut();
    }, 5000);
}
