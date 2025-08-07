# FIX: TẤT CẢ CÁC TAB HIỆN RA CÙNG LÚC

## Vấn đề
Sau khi thay đổi từ `tab-pane fade` sang `simple-pane`, tất cả các tab content hiển thị cùng lúc thay vì chỉ 1 tab active.

## Nguyên nhân
1. **Bootstrap CSS bị mất**: `tab-pane` class có CSS để ẩn các tab không active
2. **Custom CSS không đủ mạnh**: CSS `display: none` bị override bởi CSS khác
3. **JavaScript chưa force hide**: Chỉ dựa vào CSS mà không dùng jQuery `.hide()`

## Giải pháp đã áp dụng

### 1. Force CSS với !important
```css
.simple-pane {
    display: none !important;
}
.simple-pane.active {
    display: block !important;
}
```

### 2. Inject CSS trong JavaScript
```javascript
// Force CSS to ensure tabs are hidden/shown correctly
$('<style>.simple-pane { display: none !important; } .simple-pane.active { display: block !important; }</style>').appendTo('head');
```

### 3. Force Hide trong JavaScript
```javascript
function initializeTabs() {
    // Hide all panes first
    tabPanes.removeClass('active').hide();
    
    // Then activate target tab
    activateSimpleTab(activeTab);
}

function activateSimpleTab(tabName) {
    // Force hide all panes first
    $('.simple-pane').removeClass('active').hide();
    $('.simple-tab').removeClass('active');
    
    // Show only the target pane
    var targetPane = $('#' + tabName);
    if (targetPane.length > 0) {
        targetPane.addClass('active').show(); // .show() để chắc chắn
        targetTab.addClass('active');
    }
}
```

## Test Files

### 1. debug_simple_tabs.html
- URL: `http://localhost/NLNganh/view/student/debug_simple_tabs.html`
- Features:
  - Debug info hiển thị số panes visible/active
  - Buttons để test hide/show
  - Console logs chi tiết
  - Red border cho hidden panes, green cho active

### 2. Test với URL parameter
- URL: `http://localhost/NLNganh/view/student/debug_simple_tabs.html?tab=report`
- Nên chỉ hiển thị tab "Biên bản"

## Expected Behavior (Sau khi fix)

### Normal Flow:
1. Page load → Tất cả panes hidden trừ active pane
2. Click tab → Hide all, show only clicked tab
3. URL parameter → Show only tab specified in URL

### Debug Info trong debug_simple_tabs.html:
- **All Panes:** 3
- **Visible Panes:** 1 (chỉ active pane)
- **Active Panes:** 1

### Console Output:
```
=== INITIALIZING SIMPLE TABS ===
Tab links: 3
Tab panes: 3
Target tab: report
Activating tab: report
Tab activated: report
Pane visible: true
```

## Nếu vẫn có vấn đề

### Kiểm tra trong Browser Console:
```javascript
// Kiểm tra số panes hiển thị
console.log('Visible panes:', $('.simple-pane:visible').length);
console.log('All panes:', $('.simple-pane').length);

// Force hide all
$('.simple-pane').hide();

// Show only one
$('#report').show();
```

### Manual Debug Commands:
```javascript
// Hide all panes
$('.simple-pane').removeClass('active').hide();

// Show specific pane
$('#report').addClass('active').show();

// Check visibility
$('.simple-pane').each(function() {
    console.log(this.id + ':', $(this).is(':visible'));
});
```

## Kết luận

Với 3 lớp bảo vệ:
1. ✅ CSS với `!important`
2. ✅ Dynamic CSS injection
3. ✅ JavaScript `.hide()/.show()`

**Bây giờ chỉ nên có 1 tab hiển thị tại một thời điểm.**

Hãy test file debug_simple_tabs.html trước, nếu nó hoạt động đúng thì view_project.php cũng sẽ hoạt động tương tự!
