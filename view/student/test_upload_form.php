<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Upload Form</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Test Upload Form</h2>
        
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
        
        <form action="/NLNganh/view/student/update_proposal_file.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="project_id" value="TEST_PROJECT_001">
            
            <div class="form-group">
                <label for="update_reason">Lý do cập nhật:</label>
                <textarea class="form-control" id="update_reason" name="update_reason" 
                    rows="3" placeholder="Test reason" required>Test upload from simple form</textarea>
            </div>
            
            <div class="form-group">
                <label for="proposal_file">File thuyết minh:</label>
                <input type="file" class="form-control-file" id="proposal_file" 
                    name="proposal_file" required accept=".pdf,.doc,.docx">
            </div>
            
            <button type="submit" class="btn btn-primary">Upload Test</button>
        </form>
        
        <hr>
        <h3>Session Debug:</h3>
        <pre><?php print_r($_SESSION); ?></pre>
    </div>
</body>
</html>
