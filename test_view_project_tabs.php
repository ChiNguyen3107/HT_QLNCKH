<!DOCTYPE html>
<html>
<head>
    <title>Test View Project Tabs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        .tab-content { border: 1px solid #dee2e6; border-top: none; padding: 20px; }
        .nav-tabs { border-bottom: 1px solid #dee2e6; }
        .debug-info { background: #f8f9fa; padding: 10px; margin: 10px 0; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1>Test View Project - Tab Thuyết Minh</h1>
        
        <?php
        include 'include/connect.php';
        
        // Test với đề tài DT0000001
        $project_id = 'DT0000001';
        
        // Query để lấy thông tin đề tài
        $sql = "SELECT dt.*, 
                       CONCAT(gv.GV_HOGV, ' ', gv.GV_TENGV) AS GV_HOTEN, 
                       gv.GV_EMAIL,
                       ldt.LDT_TENLOAI,
                       lvnc.LVNC_TEN,
                       lvut.LVUT_TEN,
                       hd.HD_NGAYTAO,
                       hd.HD_NGAYBD,
                       hd.HD_NGAYKT,
                       hd.HD_TONGKINHPHI
                FROM de_tai_nghien_cuu dt
                LEFT JOIN giang_vien gv ON dt.GV_MAGV = gv.GV_MAGV
                LEFT JOIN loai_de_tai ldt ON dt.LDT_MA = ldt.LDT_MA
                LEFT JOIN linh_vuc_nghien_cuu lvnc ON dt.LVNC_MA = lvnc.LVNC_MA
                LEFT JOIN linh_vuc_uu_tien lvut ON dt.LVUT_MA = lvut.LVUT_MA
                LEFT JOIN hop_dong hd ON dt.HD_MA = hd.HD_MA
                WHERE dt.DT_MADT = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $project_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo "<div class='alert alert-danger'>Không tìm thấy đề tài $project_id</div>";
            exit;
        }
        
        $project = $result->fetch_assoc();
        
        // Debug info
        echo "<div class='debug-info'>";
        echo "<h5>Debug Info:</h5>";
        echo "<p><strong>DT_FILEBTM:</strong> '" . htmlspecialchars($project['DT_FILEBTM'] ?? 'NULL') . "'</p>";
        echo "<p><strong>!empty(DT_FILEBTM):</strong> " . (!empty($project['DT_FILEBTM']) ? 'TRUE' : 'FALSE') . "</p>";
        echo "<p><strong>isset(DT_FILEBTM):</strong> " . (isset($project['DT_FILEBTM']) ? 'TRUE' : 'FALSE') . "</p>";
        echo "</div>";
        ?>
        
        <!-- Tabs -->
        <ul class="nav nav-tabs" id="documentTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="proposal-tab" data-toggle="tab" href="#proposal" role="tab" aria-controls="proposal" aria-selected="true">
                    <i class="fas fa-file-alt mr-1"></i> Thuyết minh
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="contract-tab" data-toggle="tab" href="#contract" role="tab" aria-controls="contract" aria-selected="false">
                    <i class="fas fa-file-contract mr-1"></i> Hợp đồng
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="decision-tab" data-toggle="tab" href="#decision" role="tab" aria-controls="decision" aria-selected="false">
                    <i class="fas fa-gavel mr-1"></i> Quyết định
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="evaluation-tab" data-toggle="tab" href="#evaluation" role="tab" aria-controls="evaluation" aria-selected="false">
                    <i class="fas fa-clipboard-check mr-1"></i> Đánh giá
                </a>
            </li>
        </ul>
        
        <div class="tab-content" id="documentTabsContent">
            <!-- Tab Thuyết minh -->
            <div class="tab-pane fade show active" id="proposal" role="tabpanel" aria-labelledby="proposal-tab">
                <h4>Tab Thuyết Minh</h4>
                
                <?php if (!empty($project['DT_FILEBTM'])): ?>
                    <div class="proposal-file-current">
                        <h6 class="text-primary mb-3"><i class="fas fa-file-alt mr-2"></i>File thuyết minh hiện tại</h6>
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <i class="far fa-file-pdf file-icon text-danger mr-2"></i>
                                <span class="font-weight-medium"><?php echo htmlspecialchars($project['DT_FILEBTM']); ?></span>
                            </div>
                            <?php 
                                $dtFile2 = $project['DT_FILEBTM'] ?? '';
                                $proposalHref2 = '';
                                if ($dtFile2) {
                                    if (strpos($dtFile2, '/') !== false || strpos($dtFile2, '\\') !== false) {
                                        $webPath2 = preg_replace('#^\.\./\.\./#', '', str_replace('\\\\','/',$dtFile2));
                                        $proposalHref2 = '/NLNganh/' . ltrim($webPath2, '/');
                                    } else {
                                        $proposalHref2 = '/NLNganh/uploads/project_files/' . $dtFile2;
                                    }
                                }
                            ?>
                            <a href="<?php echo htmlspecialchars($proposalHref2); ?>"
                                class="btn btn-sm btn-outline-primary" download>
                                <i class="fas fa-download mr-1"></i> Tải xuống
                            </a>
                        </div>
                        <small class="text-muted d-block mt-2">
                            <i class="far fa-calendar-alt mr-1"></i>
                            Ngày tạo đề tài: <?php echo date('d/m/Y', strtotime($project['DT_NGAYTAO'])); ?>
                        </small>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle mr-2"></i> Chưa có file thuyết minh.
                    </div>
                <?php endif; ?>
                
                <!-- Test form upload -->
                <div class="mt-4">
                    <h6>Test Upload Form</h6>
                    <form action="/NLNganh/view/student/update_proposal_file.php" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="project_id" value="<?php echo htmlspecialchars($project['DT_MADT']); ?>">
                        
                        <div class="form-group">
                            <label for="proposal_update_reason">Lý do cập nhật</label>
                            <textarea class="form-control" name="update_reason" rows="3" required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="proposal_file">File thuyết minh mới</label>
                            <input type="file" class="form-control-file" name="proposal_file" required accept=".pdf,.doc,.docx">
                        </div>
                        
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-upload mr-2"></i> Cập nhật file thuyết minh
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Tab Hợp đồng -->
            <div class="tab-pane fade" id="contract" role="tabpanel" aria-labelledby="contract-tab">
                <h4>Tab Hợp Đồng</h4>
                <p>Nội dung tab hợp đồng...</p>
            </div>
            
            <!-- Tab Quyết định -->
            <div class="tab-pane fade" id="decision" role="tabpanel" aria-labelledby="decision-tab">
                <h4>Tab Quyết Định</h4>
                <p>Nội dung tab quyết định...</p>
            </div>
            
            <!-- Tab Đánh giá -->
            <div class="tab-pane fade" id="evaluation" role="tabpanel" aria-labelledby="evaluation-tab">
                <h4>Tab Đánh Giá</h4>
                <p>Nội dung tab đánh giá...</p>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>




