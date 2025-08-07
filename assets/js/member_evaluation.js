// File: member_evaluation.js
// JavaScript để xử lý đánh giá từng thành viên hội đồng

let currentMemberEvaluation = null;

$(document).ready(function() {
    // Khởi tạo các event handlers
    initializeMemberEvaluationHandlers();
});

// Khởi tạo các event handlers
function initializeMemberEvaluationHandlers() {
    // Handler cho việc mở modal đánh giá thành viên
    $(document).on('click', '.evaluate-member-btn', function() {
        const memberId = $(this).data('member-id');
        const memberName = $(this).data('member-name');
        const memberRole = $(this).data('member-role');
        openMemberEvaluationModal(memberId, memberName, memberRole);
    });
    
    // Handler cho việc lưu điểm
    $(document).on('click', '#saveMemberScores', function() {
        saveMemberScores();
    });
    
    // Handler cho việc upload file
    $(document).on('submit', '#memberFileUploadForm', function(e) {
        e.preventDefault();
        uploadMemberFile();
    });
    
    // Handler cho việc xóa file
    $(document).on('click', '.delete-member-file-btn', function() {
        const fileId = $(this).data('file-id');
        const fileName = $(this).data('file-name');
        deleteMemberFile(fileId, fileName);
    });
    
    // Tự động tính điểm trung bình khi nhập điểm
    $(document).on('input', '.score-input', function() {
        calculateAverageScore();
    });
}

// Mở modal đánh giá thành viên
function openMemberEvaluationModal(memberId, memberName, memberRole) {
    const projectId = $('input[name="project_id"]').val();
    const decisionId = $('input[name="decision_id"]').val();
    
    if (!projectId || !decisionId) {
        showAlert('Không tìm thấy thông tin đề tài hoặc quyết định', 'danger');
        return;
    }
    
    // Tạo modal HTML
    const modalHtml = createMemberEvaluationModal(memberId, memberName, memberRole);
    
    // Remove existing modal if any
    $('#memberEvaluationModal').remove();
    
    // Add modal to body
    $('body').append(modalHtml);
    
    // Show modal
    $('#memberEvaluationModal').modal('show');
    
    // Load existing evaluation data
    loadMemberEvaluationData(memberId, decisionId);
}

// Tạo HTML cho modal đánh giá thành viên
function createMemberEvaluationModal(memberId, memberName, memberRole) {
    return `
        <div class="modal fade" id="memberEvaluationModal" tabindex="-1" role="dialog" aria-labelledby="memberEvaluationModalLabel">
            <div class="modal-dialog modal-xl" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="memberEvaluationModalLabel">
                            <i class="fas fa-user-graduate mr-2"></i>Đánh giá thành viên: ${memberName}
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <div class="alert alert-info">
                                    <strong>Thành viên:</strong> ${memberName} <span class="badge badge-secondary ml-2">${memberRole}</span><br>
                                    <strong>Mã GV:</strong> ${memberId}
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tabs -->
                        <ul class="nav nav-tabs" id="evaluationTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="scores-tab" data-toggle="tab" href="#scores" role="tab">
                                    <i class="fas fa-star mr-1"></i>Điểm đánh giá
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="comments-tab" data-toggle="tab" href="#comments" role="tab">
                                    <i class="fas fa-comments mr-1"></i>Nhận xét
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="files-tab" data-toggle="tab" href="#files" role="tab">
                                    <i class="fas fa-file-upload mr-1"></i>File đánh giá
                                </a>
                            </li>
                        </ul>
                        
                        <div class="tab-content mt-3" id="evaluationTabsContent">
                            <!-- Tab Điểm đánh giá -->
                            <div class="tab-pane fade show active" id="scores" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="scoreContent">
                                                <i class="fas fa-book mr-1"></i>Điểm nội dung (0-10) <span class="text-danger">*</span>
                                            </label>
                                            <input type="number" class="form-control score-input" id="scoreContent" 
                                                   min="0" max="10" step="0.1" placeholder="0.0">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="scorePresentation">
                                                <i class="fas fa-presentation mr-1"></i>Điểm trình bày (0-10) <span class="text-danger">*</span>
                                            </label>
                                            <input type="number" class="form-control score-input" id="scorePresentation" 
                                                   min="0" max="10" step="0.1" placeholder="0.0">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="scoreResponse">
                                                <i class="fas fa-question-circle mr-1"></i>Điểm trả lời (0-10) <span class="text-danger">*</span>
                                            </label>
                                            <input type="number" class="form-control score-input" id="scoreResponse" 
                                                   min="0" max="10" step="0.1" placeholder="0.0">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="alert alert-secondary">
                                            <strong>Điểm trung bình:</strong> 
                                            <span id="averageScore" class="badge badge-primary badge-lg">0.0</span>/10
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Tab Nhận xét -->
                            <div class="tab-pane fade" id="comments" role="tabpanel">
                                <div class="form-group">
                                    <label for="positiveComment">
                                        <i class="fas fa-thumbs-up mr-1 text-success"></i>Nhận xét tích cực
                                    </label>
                                    <textarea class="form-control" id="positiveComment" rows="3" 
                                              placeholder="Nhập những điểm tích cực của đề tài..."></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label for="improvementComment">
                                        <i class="fas fa-exclamation-triangle mr-1 text-warning"></i>Nhận xét cần cải thiện
                                    </label>
                                    <textarea class="form-control" id="improvementComment" rows="3" 
                                              placeholder="Nhập những điểm cần cải thiện..."></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label for="suggestion">
                                        <i class="fas fa-lightbulb mr-1 text-info"></i>Kiến nghị
                                    </label>
                                    <textarea class="form-control" id="suggestion" rows="3" 
                                              placeholder="Nhập kiến nghị cho đề tài..."></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label for="generalComment">
                                        <i class="fas fa-comment mr-1"></i>Nhận xét tổng quát
                                    </label>
                                    <textarea class="form-control" id="generalComment" rows="4" 
                                              placeholder="Nhận xét tổng quát về đề tài..."></textarea>
                                </div>
                            </div>
                            
                            <!-- Tab File đánh giá -->
                            <div class="tab-pane fade" id="files" role="tabpanel">
                                <div class="mb-4">
                                    <h6><i class="fas fa-upload mr-2"></i>Upload file đánh giá</h6>
                                    <form id="memberFileUploadForm" enctype="multipart/form-data">
                                        <input type="hidden" id="evalMemberId" value="${memberId}">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <div class="form-group">
                                                    <label for="evaluationFile">Chọn file đánh giá</label>
                                                    <div class="custom-file">
                                                        <input type="file" class="custom-file-input" id="evaluationFile" 
                                                               name="evaluation_file" accept=".pdf,.doc,.docx,.txt" required>
                                                        <label class="custom-file-label" for="evaluationFile">Chọn file...</label>
                                                    </div>
                                                    <small class="form-text text-muted">
                                                        PDF, DOC, DOCX, TXT - Tối đa 10MB
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="fileDescription">Mô tả</label>
                                                    <input type="text" class="form-control" id="fileDescription" 
                                                           name="file_description" placeholder="Mô tả file...">
                                                </div>
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-success btn-sm">
                                            <i class="fas fa-upload mr-1"></i>Upload File
                                        </button>
                                    </form>
                                </div>
                                
                                <div id="memberFilesList">
                                    <!-- Danh sách file sẽ được load ở đây -->
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times mr-1"></i>Đóng
                        </button>
                        <button type="button" class="btn btn-primary" id="saveMemberScores">
                            <i class="fas fa-save mr-1"></i>Lưu đánh giá
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
}

// Load dữ liệu đánh giá của thành viên
function loadMemberEvaluationData(memberId, decisionId) {
    $.ajax({
        url: 'member_evaluation_handler.php',
        method: 'POST',
        data: {
            action: 'get_member_evaluation',
            project_id: $('input[name="project_id"]').val(),
            decision_id: decisionId,
            member_id: memberId
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                populateEvaluationForm(response.evaluation);
                displayMemberFiles(response.files);
            } else {
                showAlert('Lỗi tải dữ liệu: ' + response.message, 'danger');
            }
        },
        error: function(xhr, status, error) {
            showAlert('Lỗi kết nối: ' + error, 'danger');
        }
    });
}

// Điền dữ liệu vào form
function populateEvaluationForm(evaluation) {
    if (evaluation.TV_DIEM_NOIDUNG) $('#scoreContent').val(evaluation.TV_DIEM_NOIDUNG);
    if (evaluation.TV_DIEM_TRINHBAY) $('#scorePresentation').val(evaluation.TV_DIEM_TRINHBAY);
    if (evaluation.TV_DIEM_TRALOI) $('#scoreResponse').val(evaluation.TV_DIEM_TRALOI);
    
    if (evaluation.TV_NHANXET_TICHHOP) $('#positiveComment').val(evaluation.TV_NHANXET_TICHHOP);
    if (evaluation.TV_NHANXET_CANHBAO) $('#improvementComment').val(evaluation.TV_NHANXET_CANHBAO);
    if (evaluation.TV_KIENNGHI) $('#suggestion').val(evaluation.TV_KIENNGHI);
    if (evaluation.TV_DANHGIA) $('#generalComment').val(evaluation.TV_DANHGIA);
    
    calculateAverageScore();
}

// Hiển thị danh sách file
function displayMemberFiles(files) {
    let filesHtml = '';
    
    if (files.length === 0) {
        filesHtml = '<div class="alert alert-info">Chưa có file đánh giá nào.</div>';
    } else {
        filesHtml = '<h6><i class="fas fa-files mr-2"></i>Danh sách file đánh giá</h6>';
        files.forEach(file => {
            const fileSize = formatFileSize(file.MEF_FILESIZE);
            const uploadDate = formatDate(file.MEF_UPLOADDATE);
            
            filesHtml += `
                <div class="card mb-2">
                    <div class="card-body py-2">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h6 class="mb-1">${file.MEF_FILENAME}</h6>
                                <small class="text-muted">
                                    <i class="fas fa-calendar mr-1"></i>${uploadDate} | 
                                    <i class="fas fa-weight mr-1"></i>${fileSize}
                                    ${file.MEF_DESCRIPTION ? ' | ' + file.MEF_DESCRIPTION : ''}
                                </small>
                            </div>
                            <div class="col-md-4 text-right">
                                <a href="/NLNganh/uploads/member_evaluations/${file.MEF_FILEPATH}" 
                                   class="btn btn-sm btn-outline-primary mr-2" download>
                                    <i class="fas fa-download"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-danger delete-member-file-btn" 
                                        data-file-id="${file.MEF_ID}" data-file-name="${file.MEF_FILENAME}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
    }
    
    $('#memberFilesList').html(filesHtml);
}

// Tính điểm trung bình
function calculateAverageScore() {
    const content = parseFloat($('#scoreContent').val()) || 0;
    const presentation = parseFloat($('#scorePresentation').val()) || 0;
    const response = parseFloat($('#scoreResponse').val()) || 0;
    
    const average = (content + presentation + response) / 3;
    $('#averageScore').text(average.toFixed(1));
    
    // Thay đổi màu badge theo điểm
    const badge = $('#averageScore');
    badge.removeClass('badge-primary badge-success badge-warning badge-danger');
    
    if (average >= 8) {
        badge.addClass('badge-success');
    } else if (average >= 6.5) {
        badge.addClass('badge-primary');
    } else if (average >= 5) {
        badge.addClass('badge-warning');
    } else {
        badge.addClass('badge-danger');
    }
}

// Lưu điểm đánh giá
function saveMemberScores() {
    const memberId = $('#evalMemberId').val();
    const projectId = $('input[name="project_id"]').val();
    const decisionId = $('input[name="decision_id"]').val();
    
    const data = {
        action: 'save_member_scores',
        project_id: projectId,
        decision_id: decisionId,
        member_id: memberId,
        score_content: $('#scoreContent').val(),
        score_presentation: $('#scorePresentation').val(),
        score_response: $('#scoreResponse').val(),
        positive_comment: $('#positiveComment').val(),
        improvement_comment: $('#improvementComment').val(),
        suggestion: $('#suggestion').val(),
        general_comment: $('#generalComment').val()
    };
    
    // Validate
    if (!data.score_content || !data.score_presentation || !data.score_response) {
        showAlert('Vui lòng nhập đầy đủ điểm đánh giá', 'warning');
        return;
    }
    
    $.ajax({
        url: 'member_evaluation_handler.php',
        method: 'POST',
        data: data,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showAlert(response.message, 'success');
                // Refresh page sau khi lưu thành công
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showAlert('Lỗi: ' + response.message, 'danger');
            }
        },
        error: function(xhr, status, error) {
            showAlert('Lỗi kết nối: ' + error, 'danger');
        }
    });
}

// Upload file đánh giá
function uploadMemberFile() {
    const formData = new FormData();
    const fileInput = $('#evaluationFile')[0];
    const file = fileInput.files[0];
    
    if (!file) {
        showAlert('Vui lòng chọn file', 'warning');
        return;
    }
    
    formData.append('action', 'upload_member_file');
    formData.append('project_id', $('input[name="project_id"]').val());
    formData.append('decision_id', $('input[name="decision_id"]').val());
    formData.append('member_id', $('#evalMemberId').val());
    formData.append('evaluation_file', file);
    formData.append('file_description', $('#fileDescription').val());
    
    $.ajax({
        url: 'member_evaluation_handler.php',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showAlert(response.message, 'success');
                // Clear form
                $('#memberFileUploadForm')[0].reset();
                $('.custom-file-label').text('Chọn file...');
                // Reload file list
                loadMemberEvaluationData($('#evalMemberId').val(), $('input[name="decision_id"]').val());
            } else {
                showAlert('Lỗi: ' + response.message, 'danger');
            }
        },
        error: function(xhr, status, error) {
            showAlert('Lỗi upload: ' + error, 'danger');
        }
    });
}

// Xóa file đánh giá
function deleteMemberFile(fileId, fileName) {
    if (!confirm(`Bạn có chắc chắn muốn xóa file "${fileName}"?`)) {
        return;
    }
    
    $.ajax({
        url: 'member_evaluation_handler.php',
        method: 'POST',
        data: {
            action: 'delete_member_file',
            project_id: $('input[name="project_id"]').val(),
            decision_id: $('input[name="decision_id"]').val(),
            member_id: $('#evalMemberId').val(),
            file_id: fileId
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showAlert(response.message, 'success');
                // Reload file list
                loadMemberEvaluationData($('#evalMemberId').val(), $('input[name="decision_id"]').val());
            } else {
                showAlert('Lỗi: ' + response.message, 'danger');
            }
        },
        error: function(xhr, status, error) {
            showAlert('Lỗi xóa file: ' + error, 'danger');
        }
    });
}

// Utility functions
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('vi-VN') + ' ' + date.toLocaleTimeString('vi-VN');
}

function showAlert(message, type = 'info') {
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
    
    // Add new alert
    $('#memberEvaluationModal .modal-body').prepend(alertHtml);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        $('.alert-dismissible').fadeOut();
    }, 5000);
}

// Custom file input label update
$(document).on('change', '.custom-file-input', function() {
    const fileName = $(this).val().split('\\').pop();
    $(this).next('.custom-file-label').text(fileName || 'Chọn file...');
});
