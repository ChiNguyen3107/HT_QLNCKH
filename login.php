<?php
// filepath: d:\xampp\htdocs\NLNganh\login.php
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <h3 class="text-center">Đăng nhập</h3>
                <form action="login_process.php" method="POST">
                    <div class="form-group">
                        <label for="username">Tên đăng nhập</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Mật khẩu</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Đăng Nhập</button>
                </form>
                <?php
                if (isset($_GET['error'])) {
                    if ($_GET['error'] == 'invalid_credentials') {
                        echo '<div class="alert alert-danger mt-3">Tên đăng nhập hoặc mật khẩu không đúng!</div>';
                    } elseif ($_GET['error'] == 'role') {
                        echo '<div class="alert alert-danger mt-3">Bạn không có quyền truy cập!</div>';
                    }
                }
                ?>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>