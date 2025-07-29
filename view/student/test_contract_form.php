<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Contract Form</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Test Contract Form</h2>
        
        <?php
        session_start();
        if (isset($_SESSION['success_message'])) {
            echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
            unset($_SESSION['success_message']);
        }
        if (isset($_SESSION['error_message'])) {
            echo '<div class="alert alert-danger">' . $_SESSION['error_message'] . '</div>';
            unset($_SESSION['error_message']);
        }
        ?>
        
        <div class="card">
            <div class="card-body">
                <form action="/NLNganh/view/student/update_contract_info.php" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="project_id" value="TEST_PROJECT_001">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="contract_code">
                                    <i class="fas fa-barcode mr-1"></i> Mã hợp đồng <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="contract_code" name="contract_code" 
                                    placeholder="HD001" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="contract_date">
                                    <i class="far fa-calendar-alt mr-1"></i> Ngày tạo hợp đồng <span class="text-danger">*</span>
                                </label>
                                <input type="date" class="form-control" id="contract_date" name="contract_date" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="start_date">
                                    <i class="fas fa-play mr-1"></i> Ngày bắt đầu <span class="text-danger">*</span>
                                </label>
                                <input type="date" class="form-control" id="start_date" name="start_date" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="end_date">
                                    <i class="fas fa-stop mr-1"></i> Ngày kết thúc <span class="text-danger">*</span>
                                </label>
                                <input type="date" class="form-control" id="end_date" name="end_date" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="total_budget">
                            <i class="fas fa-money-bill-wave mr-1"></i> Tổng kinh phí (VNĐ) <span class="text-danger">*</span>
                        </label>
                        <input type="number" class="form-control" id="total_budget" name="total_budget" 
                            placeholder="5000000" min="0" step="1000" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="contract_description">
                            <i class="fas fa-align-left mr-1"></i> Mô tả hợp đồng
                        </label>
                        <textarea class="form-control" id="contract_description" name="contract_description" 
                            rows="3" placeholder="Mô tả nội dung hợp đồng">Test contract description</textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="update_reason">
                            <i class="fas fa-edit mr-1"></i> Lý do cập nhật <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" id="update_reason" name="update_reason" 
                            rows="2" required>Test contract creation</textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="contract_file">
                            <i class="fas fa-file mr-1"></i> File hợp đồng <span class="text-danger">*</span>
                        </label>
                        <input type="file" class="form-control-file" id="contract_file" 
                            name="contract_file" required accept=".pdf,.doc,.docx">
                    </div>
                    
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save mr-1"></i> Tạo hợp đồng test
                    </button>
                </form>
            </div>
        </div>
        
        <hr>
        <h3>Session Debug:</h3>
        <pre><?php print_r($_SESSION); ?></pre>
    </div>
</body>
</html>
