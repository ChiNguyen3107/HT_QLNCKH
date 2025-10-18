<?php
// filepath: d:\xampp\htdocs\NLNganh\view\research\publications.php

// Bao gồm file session.php để kiểm tra phiên đăng nhập và vai trò
include '../../include/session.php';
checkResearchManagerRole();

// Kết nối database
include '../../include/connect.php';

// Khởi tạo biến lọc và phân trang
$year_filter = isset($_GET['year']) ? intval($_GET['year']) : 0;
$type_filter = isset($_GET['type']) ? $_GET['type'] : '';
$faculty_filter = isset($_GET['faculty']) ? $_GET['faculty'] : '';
$search_term = isset($_GET['search']) ? $_GET['search'] : '';

// Phân trang
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$items_per_page = 10;
$offset = ($page - 1) * $items_per_page;

// Giả lập bảng ấn phẩm (trong thực tế cần tạo bảng này trong CSDL)
// Đây là dữ liệu mẫu
$publications = array(
    array(
        'id' => 'PUB001',
        'title' => 'Nghiên cứu ứng dụng trí tuệ nhân tạo trong dự đoán lũ lụt tại đồng bằng sông Cửu Long',
        'authors' => 'Nguyễn Văn A, Trần Thị B, Lê Văn C',
        'journal' => 'Tạp chí Khoa học và Công nghệ Việt Nam',
        'year' => 2023,
        'volume' => '65',
        'issue' => '3',
        'pages' => '145-160',
        'doi' => '10.1234/abcd.2023.01',
        'type' => 'Bài báo khoa học',
        'faculty' => 'Khoa Công nghệ thông tin',
        'url' => 'https://example.com/pub001'
    ),
    array(
        'id' => 'PUB002',
        'title' => 'Phát triển mô hình quản lý hiệu quả trong doanh nghiệp nhỏ và vừa tại Việt Nam',
        'authors' => 'Phạm Văn D, Nguyễn Thị E',
        'journal' => 'Tạp chí Kinh tế và Quản lý',
        'year' => 2022,
        'volume' => '42',
        'issue' => '2',
        'pages' => '78-95',
        'doi' => '10.5678/efgh.2022.02',
        'type' => 'Bài báo khoa học',
        'faculty' => 'Khoa Kinh tế',
        'url' => 'https://example.com/pub002'
    ),
    array(
        'id' => 'PUB003',
        'title' => 'Ứng dụng mô hình học máy trong dự đoán kết quả học tập của sinh viên đại học',
        'authors' => 'Lê Thị F, Trần Văn G',
        'journal' => 'Tạp chí Giáo dục',
        'year' => 2023,
        'volume' => '33',
        'issue' => '1',
        'pages' => '56-72',
        'doi' => '10.9012/ijkl.2023.03',
        'type' => 'Bài báo khoa học',
        'faculty' => 'Khoa Công nghệ thông tin',
        'url' => 'https://example.com/pub003'
    ),
    array(
        'id' => 'PUB004',
        'title' => 'Các hệ thống quản lý dự án hiện đại: Một nghiên cứu so sánh',
        'authors' => 'Nguyễn Văn H, Phạm Thị I',
        'publisher' => 'NXB Khoa học kỹ thuật',
        'year' => 2021,
        'isbn' => '978-604-913-856-0',
        'type' => 'Sách',
        'faculty' => 'Khoa Công nghệ thông tin',
        'url' => 'https://example.com/pub004'
    ),
    array(
        'id' => 'PUB005',
        'title' => 'Phân tích đặc điểm sinh học của các loài cá nước ngọt ở đồng bằng sông Cửu Long',
        'authors' => 'Trần Văn J, Lê Thị K, Phạm Văn L',
        'journal' => 'Tạp chí Sinh học',
        'year' => 2022,
        'volume' => '44',
        'issue' => '4',
        'pages' => '210-228',
        'doi' => '10.3456/mnop.2022.04',
        'type' => 'Bài báo khoa học',
        'faculty' => 'Khoa Nông nghiệp',
        'url' => 'https://example.com/pub005'
    ),
    array(
        'id' => 'PUB006',
        'title' => 'Nghiên cứu đặc tính của vật liệu nano ứng dụng trong lĩnh vực y sinh',
        'authors' => 'Nguyễn Thị M, Trần Văn N',
        'conference' => 'Hội nghị Khoa học Quốc gia về Vật liệu tiên tiến',
        'location' => 'Hà Nội, Việt Nam',
        'year' => 2023,
        'pages' => '125-132',
        'type' => 'Báo cáo hội nghị',
        'faculty' => 'Khoa Vật lý',
        'url' => 'https://example.com/pub006'
    ),
    array(
        'id' => 'PUB007',
        'title' => 'Phân tích tác động của biến đổi khí hậu đến nông nghiệp vùng đồng bằng sông Hồng',
        'authors' => 'Lê Văn O, Trần Thị P',
        'journal' => 'Tạp chí Khoa học Môi trường',
        'year' => 2021,
        'volume' => '29',
        'issue' => '2',
        'pages' => '87-103',
        'doi' => '10.7890/qrst.2021.05',
        'type' => 'Bài báo khoa học',
        'faculty' => 'Khoa Môi trường',
        'url' => 'https://example.com/pub007'
    ),
    array(
        'id' => 'PUB008',
        'title' => 'Phương pháp giảng dạy tiếng Anh hiệu quả cho sinh viên không chuyên ngữ',
        'authors' => 'Nguyễn Văn Q, Phạm Thị R',
        'journal' => 'Tạp chí Ngôn ngữ học',
        'year' => 2023,
        'volume' => '38',
        'issue' => '2',
        'pages' => '45-62',
        'doi' => '10.5432/uvwx.2023.06',
        'type' => 'Bài báo khoa học',
        'faculty' => 'Khoa Ngoại ngữ',
        'url' => 'https://example.com/pub008'
    ),
    array(
        'id' => 'PUB009',
        'title' => 'Giáo trình Lập trình Python cho người mới bắt đầu',
        'authors' => 'Trần Văn S, Lê Thị T',
        'publisher' => 'NXB Đại học Quốc gia',
        'year' => 2022,
        'isbn' => '978-604-913-875-2',
        'type' => 'Sách',
        'faculty' => 'Khoa Công nghệ thông tin',
        'url' => 'https://example.com/pub009'
    ),
    array(
        'id' => 'PUB010',
        'title' => 'Ứng dụng IoT trong nông nghiệp thông minh: Nghiên cứu tại tỉnh Bến Tre',
        'authors' => 'Phạm Văn U, Nguyễn Thị V, Lê Văn W',
        'journal' => 'Tạp chí Khoa học và Công nghệ Nông nghiệp',
        'year' => 2023,
        'volume' => '57',
        'issue' => '3',
        'pages' => '178-195',
        'doi' => '10.8765/yzab.2023.07',
        'type' => 'Bài báo khoa học',
        'faculty' => 'Khoa Công nghệ thông tin',
        'url' => 'https://example.com/pub010'
    ),
    array(
        'id' => 'PUB011',
        'title' => 'Nghiên cứu chế tạo và đánh giá hiệu quả của vật liệu hấp thụ sóng radar',
        'authors' => 'Nguyễn Văn X, Trần Thị Y',
        'journal' => 'Tạp chí Khoa học Vật liệu',
        'year' => 2022,
        'volume' => '51',
        'issue' => '4',
        'pages' => '234-249',
        'doi' => '10.1357/cdef.2022.08',
        'type' => 'Bài báo khoa học',
        'faculty' => 'Khoa Vật lý',
        'url' => 'https://example.com/pub011'
    ),
    array(
        'id' => 'PUB012',
        'title' => 'Đánh giá tác động của chính sách kinh tế vĩ mô đến tăng trưởng kinh tế Việt Nam giai đoạn 2010-2020',
        'authors' => 'Lê Văn Z, Phạm Thị AA',
        'journal' => 'Tạp chí Kinh tế và Phát triển',
        'year' => 2021,
        'volume' => '45',
        'issue' => '3',
        'pages' => '112-130',
        'doi' => '10.9876/ghij.2021.09',
        'type' => 'Bài báo khoa học',
        'faculty' => 'Khoa Kinh tế',
        'url' => 'https://example.com/pub012'
    ),
    array(
        'id' => 'PUB013',
        'title' => 'Nghiên cứu phát triển ứng dụng di động hỗ trợ học tập cho sinh viên đại học',
        'authors' => 'Trần Văn BB, Nguyễn Thị CC',
        'conference' => 'Hội thảo Quốc gia về Công nghệ thông tin và Giáo dục',
        'location' => 'TP.HCM, Việt Nam',
        'year' => 2023,
        'pages' => '67-78',
        'type' => 'Báo cáo hội nghị',
        'faculty' => 'Khoa Công nghệ thông tin',
        'url' => 'https://example.com/pub013'
    ),
    array(
        'id' => 'PUB014',
        'title' => 'Hệ thống pháp luật về bảo vệ môi trường tại Việt Nam: Thực trạng và giải pháp',
        'authors' => 'Nguyễn Văn DD, Lê Thị EE',
        'publisher' => 'NXB Chính trị Quốc gia',
        'year' => 2022,
        'isbn' => '978-604-913-896-5',
        'type' => 'Sách',
        'faculty' => 'Khoa Luật',
        'url' => 'https://example.com/pub014'
    ),
    array(
        'id' => 'PUB015',
        'title' => 'Nghiên cứu tác động của tự động hóa đến thị trường lao động Việt Nam',
        'authors' => 'Phạm Văn FF, Trần Thị GG',
        'journal' => 'Tạp chí Lao động và Xã hội',
        'year' => 2023,
        'volume' => '40',
        'issue' => '2',
        'pages' => '156-173',
        'doi' => '10.2468/klmn.2023.10',
        'type' => 'Bài báo khoa học',
        'faculty' => 'Khoa Kinh tế',
        'url' => 'https://example.com/pub015'
    )
);

// Lọc dữ liệu theo các điều kiện
$filtered_publications = array();

foreach ($publications as $pub) {
    // Lọc theo năm
    if ($year_filter > 0 && $pub['year'] != $year_filter) {
        continue;
    }
    
    // Lọc theo loại ấn phẩm
    if (!empty($type_filter) && $pub['type'] != $type_filter) {
        continue;
    }
    
    // Lọc theo khoa
    if (!empty($faculty_filter) && $pub['faculty'] != $faculty_filter) {
        continue;
    }
    
    // Tìm kiếm theo chuỗi
    if (!empty($search_term)) {
        $search_term_lower = strtolower($search_term);
        $found = false;
        
        if (strpos(strtolower($pub['title']), $search_term_lower) !== false) {
            $found = true;
        } elseif (strpos(strtolower($pub['authors']), $search_term_lower) !== false) {
            $found = true;
        } elseif (isset($pub['journal']) && strpos(strtolower($pub['journal']), $search_term_lower) !== false) {
            $found = true;
        } elseif (isset($pub['publisher']) && strpos(strtolower($pub['publisher']), $search_term_lower) !== false) {
            $found = true;
        } elseif (isset($pub['doi']) && strpos(strtolower($pub['doi']), $search_term_lower) !== false) {
            $found = true;
        } elseif (isset($pub['isbn']) && strpos(strtolower($pub['isbn']), $search_term_lower) !== false) {
            $found = true;
        }
        
        if (!$found) {
            continue;
        }
    }
    
    $filtered_publications[] = $pub;
}

// Phân trang
$total_items = count($filtered_publications);
$total_pages = ceil($total_items / $items_per_page);

// Giới hạn kết quả theo trang hiện tại
$paged_publications = array_slice($filtered_publications, $offset, $items_per_page);

// Lấy danh sách năm để lọc
$years = array();
foreach ($publications as $pub) {
    $years[$pub['year']] = $pub['year'];
}
krsort($years); // Sắp xếp năm từ mới đến cũ

// Lấy danh sách loại ấn phẩm
$types = array();
foreach ($publications as $pub) {
    $types[$pub['type']] = $pub['type'];
}
sort($types); // Sắp xếp theo bảng chữ cái

// Lấy danh sách khoa
$faculties = array();
foreach ($publications as $pub) {
    $faculties[$pub['faculty']] = $pub['faculty'];
}
sort($faculties); // Sắp xếp theo bảng chữ cái

// Tiêu đề trang
$page_title = "Ấn phẩm nghiên cứu | Quản lý nghiên cứu";

// Set page title
$page_title = "Ấn phẩm nghiên cứu | Quản lý nghiên cứu";

// Define any additional CSS specific to this page
$additional_css = '<style>
    /* Layout positioning - tương tự như dashboard và profile */
    #content-wrapper {
        margin-left: 260px !important;
        width: calc(100% - 260px) !important;
        padding-left: 15px !important;
        padding-right: 15px !important;
    }
    
    .container-fluid {
        padding-left: 15px !important;
        padding-right: 15px !important;
        max-width: none !important;
    }
    
    /* Đảm bảo body layout đúng */
    body {
        margin-left: 0 !important;
    }
    
    /* Enhanced publication cards */
    .publication-card {
        margin-bottom: 1.5rem;
        transition: all 0.3s ease;
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        background: white;
        border-left: 4px solid #667eea;
    }
    
    .publication-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }
    
    .publication-card .card-body {
        padding: 25px;
    }
    
    .publication-title {
        font-weight: 600;
        margin-bottom: 1rem;
        color: #5a5c69;
        font-size: 1.1rem;
        line-height: 1.4;
    }
    
    .publication-authors {
        margin-bottom: 1rem;
        font-size: 0.95rem;
        font-style: italic;
        color: #6c757d;
        font-weight: 500;
    }
    
    .publication-meta {
        font-size: 0.85rem;
        margin-bottom: 0.75rem;
        color: #858796;
        line-height: 1.5;
    }
    
    .publication-meta strong {
        color: #5a5c69;
        font-weight: 600;
    }
    
    .publication-badge {
        display: inline-block;
        padding: 0.4rem 0.8rem;
        font-size: 0.75rem;
        font-weight: 600;
        border-radius: 20px;
        margin-right: 0.5rem;
        margin-bottom: 0.5rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        background-color: #f8f9fc;
        border: 2px solid #e3e6f0;
        transition: all 0.3s ease;
    }
    
    .publication-type-article {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-color: #667eea;
    }
    
    .publication-type-book {
        background: linear-gradient(135deg, #1cc88a 0%, #17a2b8 100%);
        color: white;
        border-color: #1cc88a;
    }
    
    .publication-type-conference {
        background: linear-gradient(135deg, #f6c23e 0%, #fd7e14 100%);
        color: white;
        border-color: #f6c23e;
    }
    
    /* Enhanced buttons */
    .btn {
        border-radius: 8px;
        padding: 10px 20px;
        font-weight: 600;
        transition: all 0.3s ease;
        border: none;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-size: 0.85em;
    }
    
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    .btn-success {
        background: linear-gradient(135deg, #1cc88a 0%, #17a2b8 100%);
    }
    
    .btn-info {
        background: linear-gradient(135deg, #36b9cc 0%, #1cc88a 100%);
    }
    
    .btn-secondary {
        background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
    }
    
    .btn-sm {
        padding: 8px 16px;
        font-size: 0.8rem;
    }
    
    .action-btn {
        margin-left: 0.25rem;
        margin-right: 0.25rem;
        border-radius: 8px;
        border: 2px solid transparent;
        transition: all 0.3s ease;
    }
    
    .btn-outline-primary {
        border-color: #667eea;
        color: #667eea;
        background: transparent;
    }
    
    .btn-outline-primary:hover {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-color: #667eea;
    }
    
    .btn-outline-info {
        border-color: #36b9cc;
        color: #36b9cc;
        background: transparent;
    }
    
    .btn-outline-info:hover {
        background: linear-gradient(135deg, #36b9cc 0%, #1cc88a 100%);
        color: white;
        border-color: #36b9cc;
    }
    
    .btn-outline-danger {
        border-color: #e74a3b;
        color: #e74a3b;
        background: transparent;
    }
    
    .btn-outline-danger:hover {
        background: linear-gradient(135deg, #e74a3b 0%, #c82333 100%);
        color: white;
        border-color: #e74a3b;
    }
    
    /* Form controls */
    .form-control, .form-select {
        border-radius: 8px;
        padding: 12px 15px;
        border: 2px solid #e9ecef;
        transition: all 0.3s ease;
        font-size: 0.95em;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        transform: translateY(-1px);
    }
    
    /* Card improvements */
    .card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        background: white;
        margin-bottom: 25px;
    }
    
    .card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-bottom: none;
        padding: 20px;
        font-weight: 600;
    }
    
    .card-body {
        padding: 25px;
    }
    
    /* Modal improvements */
    .modal-content {
        border-radius: 12px;
        border: none;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    }
    
    .modal-header {
        border-radius: 12px 12px 0 0;
        border-bottom: none;
        padding: 20px;
    }
    
    .modal-body {
        padding: 25px;
    }
    
    .modal-footer {
        border-top: none;
        padding: 20px;
    }
    
    /* Pagination improvements */
    .pagination {
        border-radius: 8px;
        overflow: hidden;
    }
    
    .page-link {
        border: none;
        color: #667eea;
        transition: all 0.3s ease;
        padding: 10px 15px;
        font-weight: 500;
    }
    
    .page-link:hover {
        background-color: #667eea;
        color: white;
        transform: translateY(-1px);
    }
    
    .page-item.active .page-link {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-color: #667eea;
    }
    
    .pagination-container {
        margin-top: 2rem;
        display: flex;
        justify-content: center;
    }
    
    /* Empty state */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #6c757d;
    }
    
    .empty-state i {
        font-size: 5rem;
        color: #d1d3e2;
        margin-bottom: 25px;
        opacity: 0.5;
    }
    
    .empty-state h5 {
        color: #5a5c69;
        margin-bottom: 15px;
        font-weight: 600;
    }
    
    .empty-state p {
        color: #858796;
        margin-bottom: 25px;
        font-size: 1.1em;
    }
    
    /* Action buttons container */
    .action-buttons {
        margin-bottom: 2rem;
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        align-items: center;
    }
    
    /* Responsive improvements */
    @media (max-width: 768px) {
        .container-fluid {
            padding: 20px 15px !important;
        }
        
        #content-wrapper {
            margin-left: 0 !important;
            width: 100% !important;
        }
        
        .action-buttons {
            flex-direction: column;
            align-items: stretch;
        }
        
        .action-buttons .btn {
            margin-bottom: 10px;
            width: 100%;
        }
        
        .publication-card .row {
            flex-direction: column;
        }
        
        .action-btn {
            margin: 2px;
            font-size: 0.75rem;
            padding: 6px 12px;
        }
        
        .publication-card .col-md-3 {
            margin-top: 15px;
            text-align: center;
        }
    }
</style>';

// Include the research header
include '../../include/research_header.php';
?>

<!-- Sidebar đã được include trong header -->

<!-- Begin Page Content -->
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-book me-3"></i>
            Ấn phẩm nghiên cứu
        </h1>
        <a href="research_dashboard.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Về Dashboard
        </a>
    </div>

    <!-- Action buttons -->
    <div class="action-buttons">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPublicationModal">
            <i class="fas fa-plus me-1"></i> Thêm ấn phẩm mới
        </button>
        <button class="btn btn-success" id="importPublications">
            <i class="fas fa-file-import me-1"></i> Nhập từ file
        </button>
        <button class="btn btn-info" id="exportPublications">
            <i class="fas fa-file-export me-1"></i> Xuất danh sách
        </button>
    </div>

    <!-- Bộ lọc -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-filter me-2"></i>Bộ lọc tìm kiếm
            </h5>
        </div>
        <div class="card-body">
            <form method="GET" action="" class="filter-form">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="year" class="form-label fw-bold">
                            <i class="fas fa-calendar me-1"></i>Năm xuất bản
                        </label>
                        <select class="form-select" id="year" name="year">
                            <option value="0">Tất cả năm</option>
                            <?php foreach ($years as $year): ?>
                                <option value="<?php echo $year; ?>" <?php echo $year_filter == $year ? 'selected' : ''; ?>>
                                    <?php echo $year; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="type" class="form-label fw-bold">
                            <i class="fas fa-bookmark me-1"></i>Loại ấn phẩm
                        </label>
                        <select class="form-select" id="type" name="type">
                            <option value="">Tất cả loại</option>
                            <?php foreach ($types as $type): ?>
                                <option value="<?php echo htmlspecialchars($type); ?>" <?php echo $type_filter === $type ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="faculty" class="form-label fw-bold">
                            <i class="fas fa-building me-1"></i>Khoa/Đơn vị
                        </label>
                        <select class="form-select" id="faculty" name="faculty">
                            <option value="">Tất cả khoa</option>
                            <?php foreach ($faculties as $faculty): ?>
                                <option value="<?php echo htmlspecialchars($faculty); ?>" <?php echo $faculty_filter === $faculty ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($faculty); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="search" class="form-label fw-bold">
                            <i class="fas fa-search me-1"></i>Tìm kiếm
                        </label>
                        <input type="text" class="form-control" id="search" name="search" 
                               placeholder="Tên, tác giả, DOI..." 
                               value="<?php echo htmlspecialchars($search_term); ?>">
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i> Tìm kiếm
                        </button>
                        <a href="publications.php" class="btn btn-secondary ms-2">
                            <i class="fas fa-sync-alt me-1"></i> Đặt lại bộ lọc
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Ấn phẩm list -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-list me-2"></i>Danh sách ấn phẩm nghiên cứu
                <span class="badge bg-warning ms-2"><?php echo $total_items; ?> ấn phẩm</span>
            </h5>
        </div>
                    <div class="card-body">
                        <?php if (count($paged_publications) > 0): ?>
                            <?php foreach ($paged_publications as $pub): ?>
                                <div class="card publication-card">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-9">
                                                <h5 class="publication-title">
                                                    <?php echo htmlspecialchars($pub['title']); ?>
                                                </h5>
                                                <p class="publication-authors">
                                                    <?php echo htmlspecialchars($pub['authors']); ?>
                                                </p>
                                                
                                                <!-- Publication metadata -->
                                                <div class="publication-meta">
                                                    <?php if (isset($pub['journal'])): ?>
                                                        <strong>Tạp chí:</strong> <?php echo htmlspecialchars($pub['journal']); ?>, 
                                                        <strong>Năm:</strong> <?php echo htmlspecialchars($pub['year']); ?>, 
                                                        <strong>Tập:</strong> <?php echo htmlspecialchars($pub['volume']); ?>, 
                                                        <strong>Số:</strong> <?php echo htmlspecialchars($pub['issue']); ?>, 
                                                        <strong>Trang:</strong> <?php echo htmlspecialchars($pub['pages']); ?>
                                                    <?php elseif (isset($pub['conference'])): ?>
                                                        <strong>Hội nghị:</strong> <?php echo htmlspecialchars($pub['conference']); ?>, 
                                                        <strong>Địa điểm:</strong> <?php echo htmlspecialchars($pub['location']); ?>, 
                                                        <strong>Năm:</strong> <?php echo htmlspecialchars($pub['year']); ?>, 
                                                        <strong>Trang:</strong> <?php echo htmlspecialchars($pub['pages']); ?>
                                                    <?php elseif (isset($pub['publisher'])): ?>
                                                        <strong>Nhà xuất bản:</strong> <?php echo htmlspecialchars($pub['publisher']); ?>, 
                                                        <strong>Năm:</strong> <?php echo htmlspecialchars($pub['year']); ?>, 
                                                        <strong>ISBN:</strong> <?php echo htmlspecialchars($pub['isbn']); ?>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <!-- Additional metadata -->
                                                <div class="publication-meta">
                                                    <strong>Mã:</strong> <?php echo htmlspecialchars($pub['id']); ?>
                                                    <?php if (isset($pub['doi'])): ?>
                                                        | <strong>DOI:</strong> <?php echo htmlspecialchars($pub['doi']); ?>
                                                    <?php endif; ?>
                                                    | <strong>Đơn vị:</strong> <?php echo htmlspecialchars($pub['faculty']); ?>
                                                </div>
                                                
                                                <!-- Publication badges -->
                                                <div class="mt-2">
                                                    <?php
                                                    $badge_class = '';
                                                    switch($pub['type']) {
                                                        case 'Bài báo khoa học':
                                                            $badge_class = 'publication-type-article';
                                                            break;
                                                        case 'Sách':
                                                            $badge_class = 'publication-type-book';
                                                            break;
                                                        case 'Báo cáo hội nghị':
                                                            $badge_class = 'publication-type-conference';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="publication-badge <?php echo $badge_class; ?>">
                                                        <?php echo htmlspecialchars($pub['type']); ?>
                                                    </span>
                                                    <span class="publication-badge">
                                                        <?php echo htmlspecialchars($pub['year']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="col-md-3 d-flex align-items-center justify-content-end">
                                                <a href="<?php echo htmlspecialchars($pub['url']); ?>" target="_blank" class="btn btn-sm btn-outline-primary action-btn" title="Xem trực tuyến">
                                                    <i class="fas fa-external-link-alt"></i>
                                                </a>
                                                <button class="btn btn-sm btn-outline-info action-btn edit-publication" title="Sửa thông tin" data-id="<?php echo htmlspecialchars($pub['id']); ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger action-btn delete-publication" title="Xóa" data-id="<?php echo htmlspecialchars($pub['id']); ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                            <nav aria-label="Page navigation">
                                <ul class="pagination justify-content-center">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $year_filter > 0 ? '&year=' . $year_filter : ''; ?><?php echo !empty($type_filter) ? '&type=' . urlencode($type_filter) : ''; ?><?php echo !empty($faculty_filter) ? '&faculty=' . urlencode($faculty_filter) : ''; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>">
                                                <i class="fas fa-chevron-left"></i> Trước
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php
                                    $start_page = max(1, $page - 2);
                                    $end_page = min($total_pages, $page + 2);
                                    
                                    if ($start_page > 1) {
                                        echo '<li class="page-item"><a class="page-link" href="?page=1' . 
                                            ($year_filter > 0 ? '&year=' . $year_filter : '') . 
                                            (!empty($type_filter) ? '&type=' . urlencode($type_filter) : '') . 
                                            (!empty($faculty_filter) ? '&faculty=' . urlencode($faculty_filter) : '') . 
                                            (!empty($search_term) ? '&search=' . urlencode($search_term) : '') . 
                                            '">1</a></li>';
                                        
                                        if ($start_page > 2) {
                                            echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                                        }
                                    }
                                    
                                    for ($i = $start_page; $i <= $end_page; $i++) {
                                        echo '<li class="page-item ' . ($i == $page ? 'active' : '') . '">
                                            <a class="page-link" href="?page=' . $i . 
                                            ($year_filter > 0 ? '&year=' . $year_filter : '') . 
                                            (!empty($type_filter) ? '&type=' . urlencode($type_filter) : '') . 
                                            (!empty($faculty_filter) ? '&faculty=' . urlencode($faculty_filter) : '') . 
                                            (!empty($search_term) ? '&search=' . urlencode($search_term) : '') . 
                                            '">' . $i . '</a>
                                        </li>';
                                    }
                                    
                                    if ($end_page < $total_pages) {
                                        if ($end_page < $total_pages - 1) {
                                            echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                                        }
                                        
                                        echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . 
                                            ($year_filter > 0 ? '&year=' . $year_filter : '') . 
                                            (!empty($type_filter) ? '&type=' . urlencode($type_filter) : '') . 
                                            (!empty($faculty_filter) ? '&faculty=' . urlencode($faculty_filter) : '') . 
                                            (!empty($search_term) ? '&search=' . urlencode($search_term) : '') . 
                                            '">' . $total_pages . '</a></li>';
                                    }
                                    ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $year_filter > 0 ? '&year=' . $year_filter : ''; ?><?php echo !empty($type_filter) ? '&type=' . urlencode($type_filter) : ''; ?><?php echo !empty($faculty_filter) ? '&faculty=' . urlencode($faculty_filter) : ''; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>">
                                                Tiếp <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-search"></i>
                                <h5>Không tìm thấy ấn phẩm nào</h5>
                                <p>Không tìm thấy ấn phẩm nào phù hợp với tiêu chí tìm kiếm của bạn.</p>
                                <a href="publications.php" class="btn btn-primary">
                                    <i class="fas fa-sync-alt me-1"></i> Xem tất cả ấn phẩm
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
</div> <!-- /.container-fluid -->

<!-- Modal thêm ấn phẩm mới -->
<div class="modal fade" id="addPublicationModal" tabindex="-1" role="dialog" aria-labelledby="addPublicationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addPublicationModalLabel">Thêm ấn phẩm mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="publicationForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="pubType" class="form-label">Loại ấn phẩm</label>
                        <select class="form-select" id="pubType" name="pubType" required>
                            <option value="">-- Chọn loại ấn phẩm --</option>
                            <option value="Bài báo khoa học">Bài báo khoa học</option>
                            <option value="Sách">Sách</option>
                            <option value="Báo cáo hội nghị">Báo cáo hội nghị</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="pubTitle" class="form-label">Tiêu đề</label>
                        <input type="text" class="form-control" id="pubTitle" name="pubTitle" required>
                    </div>
                    <div class="mb-3">
                        <label for="pubAuthors" class="form-label">Tác giả</label>
                        <input type="text" class="form-control" id="pubAuthors" name="pubAuthors" placeholder="Ngăn cách bởi dấu phẩy" required>
                    </div>
                    
                    <!-- Thông tin cho bài báo khoa học -->
                    <div id="journalFields" class="pub-specific-fields">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="pubJournal" class="form-label">Tên tạp chí</label>
                                <input type="text" class="form-control" id="pubJournal" name="pubJournal">
                            </div>
                            <div class="col-md-2">
                                <label for="pubVolume" class="form-label">Tập</label>
                                <input type="text" class="form-control" id="pubVolume" name="pubVolume">
                            </div>
                            <div class="col-md-2">
                                <label for="pubIssue" class="form-label">Số</label>
                                <input type="text" class="form-control" id="pubIssue" name="pubIssue">
                            </div>
                            <div class="col-md-2">
                                <label for="pubPages" class="form-label">Trang</label>
                                <input type="text" class="form-control" id="pubPages" name="pubPages" placeholder="vd: 123-145">
                            </div>
                        </div>
                        <div class="mb-3 mt-3">
                            <label for="pubDOI" class="form-label">DOI</label>
                            <input type="text" class="form-control" id="pubDOI" name="pubDOI" placeholder="vd: 10.1234/abcdef">
                        </div>
                    </div>
                    
                    <!-- Thông tin cho sách -->
                    <div id="bookFields" class="pub-specific-fields">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label for="pubPublisher" class="form-label">Nhà xuất bản</label>
                                <input type="text" class="form-control" id="pubPublisher" name="pubPublisher">
                            </div>
                            <div class="col-md-4">
                                <label for="pubISBN" class="form-label">ISBN</label>
                                <input type="text" class="form-control" id="pubISBN" name="pubISBN" placeholder="vd: 978-3-16-148410-0">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Thông tin cho báo cáo hội nghị -->
                    <div id="conferenceFields" class="pub-specific-fields">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="pubConference" class="form-label">Tên hội nghị</label>
                                <input type="text" class="form-control" id="pubConference" name="pubConference">
                            </div>
                            <div class="col-md-4">
                                <label for="pubLocation" class="form-label">Địa điểm</label>
                                <input type="text" class="form-control" id="pubLocation" name="pubLocation">
                            </div>
                            <div class="col-md-2">
                                <label for="pubConfPages" class="form-label">Trang</label>
                                <input type="text" class="form-control" id="pubConfPages" name="pubConfPages">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Thông tin chung -->
                    <div class="row g-3 mt-3">
                        <div class="col-md-4">
                            <label for="pubYear" class="form-label">Năm xuất bản</label>
                            <input type="number" class="form-control" id="pubYear" name="pubYear" min="1900" max="<?php echo date('Y'); ?>" value="<?php echo date('Y'); ?>" required>
                        </div>
                        <div class="col-md-8">
                            <label for="pubFaculty" class="form-label">Khoa/Đơn vị</label>
                            <select class="form-select" id="pubFaculty" name="pubFaculty" required>
                                <option value="">-- Chọn khoa/đơn vị --</option>
                                <?php foreach ($faculties as $faculty): ?>
                                    <option value="<?php echo htmlspecialchars($faculty); ?>">
                                        <?php echo htmlspecialchars($faculty); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3 mt-3">
                        <label for="pubURL" class="form-label">URL (liên kết đến ấn phẩm)</label>
                        <input type="url" class="form-control" id="pubURL" name="pubURL" placeholder="https://">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary">Thêm ấn phẩm</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JavaScript Libraries -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JavaScript for publications functionality -->
<script>
$(document).ready(function() {
    // Auto-dismiss alerts after 5 seconds
    $(".alert").delay(5000).fadeOut(500);
    
    // Hide all specific fields initially
    $('.pub-specific-fields').hide();
    
    // Show/hide specific fields based on publication type
    $('#pubType').change(function() {
        const selectedType = $(this).val();
        $('.pub-specific-fields').hide();
        
        switch(selectedType) {
            case 'Bài báo khoa học':
                $('#journalFields').show();
                break;
            case 'Sách':
                $('#bookFields').show();
                break;
            case 'Báo cáo hội nghị':
                $('#conferenceFields').show();
                break;
        }
    });
    
    // Enhanced form validation
    $('#publicationForm').submit(function(e) {
        e.preventDefault();
        
        const form = $(this);
        const requiredFields = form.find("[required]");
        let isValid = true;
        
        requiredFields.each(function() {
            if ($(this).val().trim() === "") {
                isValid = false;
                $(this).addClass("is-invalid");
            } else {
                $(this).removeClass("is-invalid");
            }
        });
        
        if (!isValid) {
            alert("Vui lòng điền đầy đủ thông tin bắt buộc!");
            return false;
        }
        
        // Show success message
        const alert = $('<div class="alert alert-success alert-dismissible fade show" role="alert">' +
            '<i class="fas fa-check-circle me-2"></i>' +
            'Đang xử lý thêm ấn phẩm mới...' +
            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
            '</div>');
        $(".container-fluid").prepend(alert);
        
        // Simulate processing
        setTimeout(function() {
            alert.remove();
            $('#addPublicationModal').modal('hide');
            
            const successAlert = $('<div class="alert alert-info alert-dismissible fade show" role="alert">' +
                '<i class="fas fa-info-circle me-2"></i>' +
                'Chức năng thêm ấn phẩm đang được phát triển. Dữ liệu sẽ được lưu trong phiên bản tiếp theo.' +
                '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                '</div>');
            $(".container-fluid").prepend(successAlert);
        }, 2000);
    });
    
    // Import and export functionality with enhanced UX
    $('#importPublications').click(function() {
        const btn = $(this);
        const originalText = btn.html();
        
        btn.html('<i class="fas fa-spinner fa-spin me-1"></i> Đang xử lý...');
        btn.prop('disabled', true);
        
        setTimeout(function() {
            btn.html(originalText);
            btn.prop('disabled', false);
            
            alert('Chức năng nhập ấn phẩm từ file đang được phát triển.\n\nCác định dạng hỗ trợ sẽ bao gồm:\n- CSV\n- Excel (.xlsx)\n- BibTeX\n- RIS');
        }, 1500);
    });
    
    $('#exportPublications').click(function() {
        const btn = $(this);
        const originalText = btn.html();
        
        btn.html('<i class="fas fa-spinner fa-spin me-1"></i> Đang xuất...');
        btn.prop('disabled', true);
        
        setTimeout(function() {
            btn.html(originalText);
            btn.prop('disabled', false);
            
            alert('Chức năng xuất danh sách ấn phẩm đang được phát triển.\n\nCác định dạng xuất sẽ bao gồm:\n- PDF\n- Excel (.xlsx)\n- CSV\n- BibTeX');
        }, 1500);
    });
    
    // Edit publication functionality
    $('.edit-publication').click(function() {
        const pubId = $(this).data('id');
        const btn = $(this);
        const originalHtml = btn.html();
        
        btn.html('<i class="fas fa-spinner fa-spin"></i>');
        btn.prop('disabled', true);
        
        setTimeout(function() {
            btn.html(originalHtml);
            btn.prop('disabled', false);
            
            alert('Chức năng chỉnh sửa ấn phẩm ' + pubId + ' đang được phát triển.\n\nSẽ cho phép chỉnh sửa:\n- Thông tin cơ bản\n- Tác giả\n- Metadata\n- Liên kết');
        }, 1000);
    });
    
    // Delete publication functionality with confirmation
    $('.delete-publication').click(function() {
        const pubId = $(this).data('id');
        const btn = $(this);
        
        // Enhanced confirmation dialog
        const isConfirmed = confirm('⚠️ XÁC NHẬN XÓA ẤN PHẨM\n\n' +
            'Bạn có chắc chắn muốn xóa ấn phẩm này?\n' +
            'Mã ấn phẩm: ' + pubId + '\n\n' +
            '⚠️ Hành động này không thể hoàn tác!');
        
        if (isConfirmed) {
            const originalHtml = btn.html();
            btn.html('<i class="fas fa-spinner fa-spin"></i>');
            btn.prop('disabled', true);
            
            setTimeout(function() {
                btn.html(originalHtml);
                btn.prop('disabled', false);
                
                alert('Chức năng xóa ấn phẩm ' + pubId + ' đang được phát triển.\n\nTrong phiên bản hoàn chỉnh sẽ có:\n- Xác thực quyền hạn\n- Log hoạt động\n- Backup trước khi xóa');
            }, 1500);
        }
    });
    
    // Smooth scroll to top when clicking pagination
    $(".pagination a").click(function(e) {
        if (this.href.indexOf('#') === -1) {
            $('html, body').animate({
                scrollTop: $(".container-fluid").offset().top - 20
            }, 500);
        }
    });
    
    // Enhanced search functionality
    $('#search').on('keyup', function(e) {
        if (e.keyCode === 13) { // Enter key
            $(this).closest('form').submit();
        }
    });
    
    // Form reset functionality
    $('a[href="publications.php"]').click(function(e) {
        if ($(this).hasClass('btn-secondary')) {
            e.preventDefault();
            
            // Animate form reset
            $('.form-select, .form-control').animate({
                opacity: 0.5
            }, 200).animate({
                opacity: 1
            }, 200);
            
            setTimeout(function() {
                window.location.href = 'publications.php';
            }, 300);
        }
    });
    
    // Initialize Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Add tooltips to action buttons
    $('.action-btn').each(function() {
        $(this).attr('data-bs-toggle', 'tooltip');
        $(this).attr('data-bs-placement', 'top');
    });
    
    // Auto-save form data in localStorage for better UX
    $('#publicationForm input, #publicationForm select, #publicationForm textarea').on('change', function() {
        const formData = $('#publicationForm').serialize();
        localStorage.setItem('publication_form_data', formData);
    });
    
    // Restore form data on modal open
    $('#addPublicationModal').on('shown.bs.modal', function() {
        const savedData = localStorage.getItem('publication_form_data');
        if (savedData) {
            // Parse and restore form data (implementation would depend on specific needs)
            console.log('Restoring form data:', savedData);
        }
    });
    
    // Clear saved data on successful submit
    $('#publicationForm').on('submit', function() {
        localStorage.removeItem('publication_form_data');
    });
});

// Function to highlight search terms in results
function highlightSearchTerms() {
    const searchTerm = $('#search').val().trim();
    if (searchTerm) {
        $('.publication-title, .publication-authors').each(function() {
            const text = $(this).text();
            const highlightedText = text.replace(
                new RegExp(searchTerm, 'gi'),
                '<mark style="background-color: #fff3cd; padding: 2px 4px; border-radius: 3px;">$&</mark>'
            );
            $(this).html(highlightedText);
        });
    }
}

// Call highlight function after page load
$(window).on('load', function() {
    highlightSearchTerms();
});
</script>

<?php
// Include footer if needed
// include '../../include/research_footer.php';
?>
