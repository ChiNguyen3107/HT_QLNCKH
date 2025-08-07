# DEBUG: TẤT CẢ CÁC TAB VẪN HIỂN THỊ

## Vấn đề hiện tại
Mặc dù đã áp dụng nhiều phương pháp ẩn tabs, tất cả các tab content vẫn hiển thị cùng lúc.

## Các phương pháp đã thử

### 1. CSS với !important
```css
.simple-pane {
    display: none !important;
}
.simple-pane.active {
    display: block !important;
}
```

### 2. JavaScript .hide()/.show()
```javascript
$('.simple-pane').hide();
$('#targetTab').show();
```

### 3. CSS inline trực tiếp
```javascript
$(this).css({
    'display': 'none',
    'visibility': 'hidden'
});
```

### 4. Dynamic CSS injection
```javascript
$('<style>.simple-pane { display: none !important; }</style>').appendTo('head');
```

## File Debug để kiểm tra

### debug_all_tabs_showing.html
- URL: `http://localhost/NLNganh/view/student/debug_all_tabs_showing.html`
- Features:
  - Live debug panel hiển thị số panes visible/hidden
  - Red border cho hidden panes, green cho active
  - Console logs hiển thị trên page
  - Buttons để test hide/show manual

**Expected behavior:**
- Chỉ 1 pane visible
- Debug panel: "Visible Panes: 1"
- Console logs show hiding/showing operations

## Debug trong Browser Console

### Kiểm tra số panes hiển thị:
```javascript
console.log('Total panes:', $('.simple-pane').length);
console.log('Visible panes:', $('.simple-pane:visible').length);
console.log('Hidden panes:', $('.simple-pane:hidden').length);

// Liệt kê từng pane
$('.simple-pane').each(function() {
    console.log(this.id + ':', 
        'visible=' + $(this).is(':visible'), 
        'display=' + $(this).css('display'),
        'visibility=' + $(this).css('visibility')
    );
});
```

### Force hide manual:
```javascript
// Cách 1: jQuery hide
$('.simple-pane').hide();

// Cách 2: CSS display none
$('.simple-pane').css('display', 'none');

// Cách 3: CSS với !important
$('.simple-pane').each(function() {
    this.style.setProperty('display', 'none', 'important');
});

// Cách 4: Remove từ DOM tạm thời
$('.simple-pane').not('#proposal').detach();
```

### Show chỉ 1 pane:
```javascript
function showOnlyOnePane(tabName) {
    // Hide all
    $('.simple-pane').hide().css('display', 'none');
    
    // Show target
    $('#' + tabName).show().css('display', 'block');
    
    console.log('Only showing:', tabName);
    console.log('Visible count:', $('.simple-pane:visible').length);
}

// Test
showOnlyOnePane('proposal');
```

## Có thể nguyên nhân

### 1. CSS Conflicts
- Bootstrap CSS có thể override custom CSS
- Thứ tự load CSS không đúng
- Specificity không đủ cao

### 2. JavaScript không chạy
- initializeTabs() không được gọi
- jQuery chưa load
- Error trong JavaScript

### 3. HTML Structure khác
- Class names không match
- IDs không đúng
- Nested elements gây conflict

## Test Plan

### Step 1: Test debug_all_tabs_showing.html
- Nếu hoạt động → vấn đề ở view_project.php
- Nếu không hoạt động → vấn đề cơ bản với approach

### Step 2: Kiểm tra view_project.php
```javascript
// Trong console của view_project.php
console.log('SIMPLE TABS LOADED indicator:', $('#debug-indicator').length);
console.log('Simple tab links:', $('.simple-tab').length);
console.log('Simple panes:', $('.simple-pane').length);
console.log('Visible panes:', $('.simple-pane:visible').length);
```

### Step 3: Manual force trong view_project.php
```javascript
// Force hide trong view_project.php
$('.simple-pane').each(function() {
    this.style.setProperty('display', 'none', 'important');
    this.style.setProperty('visibility', 'hidden', 'important');
});

// Show chỉ 1
$('#proposal').css({
    'display': 'block',
    'visibility': 'visible'
});
```

## Fallback Solution - Nuclear Option

Nếu tất cả các cách trên không work, sử dụng:

```javascript
function nuclearHideTabs() {
    // Remove all panes from DOM
    $('.simple-pane').detach();
    
    // Add back only the active one
    var activeTabName = 'proposal'; // or from URL
    var activePane = $('#' + activeTabName);
    $('.tab-content').append(activePane);
}
```

## Next Steps

1. ✅ Test debug_all_tabs_showing.html
2. ⏳ Check view_project.php console
3. ⏳ Try manual hiding in browser
4. ⏳ Compare working vs non-working

**Mục tiêu: Chỉ 1 tab hiển thị tại 1 thời điểm!**
