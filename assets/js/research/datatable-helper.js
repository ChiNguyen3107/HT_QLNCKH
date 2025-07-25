/**
 * DataTables Helper Functions for Research Manager
 * Provides consistent initialization and configuration for datatables
 */

// Initialize DataTable with default configuration
function initDataTable(tableId, options = {}) {
    // Default options
    const defaultOptions = {
        responsive: true,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Vietnamese.json'
        },
        pageLength: 10,
        lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "Tất cả"]],
        dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        columnDefs: [{
            targets: 'no-sort',
            orderable: false
        }]
    };

    // Merge user options with defaults
    const mergedOptions = {...defaultOptions, ...options};
    const table = $('#' + tableId);
    if (!table.length) {
        console.error("Table not found:", tableId);
        return null;
    }
    // Only destroy if it's already a DataTable
    if ($.fn.DataTable.isDataTable(table)) {
        table.DataTable().destroy();
    }
    // Initialize with merged options
    return table.DataTable(mergedOptions);
}

// Export DataTable to various formats
function exportDataTable(tableId, format, filename) {
    const table = $('#' + tableId);
    if (!table.length) {
        console.error("Table not found:", tableId);
        return;
    }
    filename = filename || 'export_data';
    // Simple CSV export if DataTables buttons aren't available
    if (format === 'csv') {
        const rows = [];
        const headers = [];
        table.find('thead th').each(function() {
            headers.push($(this).text().trim());
        });
        rows.push(headers);
        table.find('tbody tr:visible').each(function() {
            const row = [];
            $(this).find('td').each(function() {
                row.push($(this).text().trim());
            });
            rows.push(row);
        });
        let csvContent = "data:text/csv;charset=utf-8,\uFEFF"; // Add BOM for Excel
        rows.forEach(rowArray => {
            const row = rowArray.join(",");
            csvContent += row + "\r\n";
        });
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", filename + ".csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        return;
    }
    // Excel export
    if (format === 'excel') {
        // If DataTables Buttons is available
        if ($.fn.DataTable.Buttons) {
            const dt = table.DataTable();
            dt.button('.buttons-excel').trigger();
        } else {
            // Fall back to CSV
            exportDataTable(tableId, 'csv', filename);
        }
        return;
    }
    alert("Định dạng xuất không được hỗ trợ.");
}

// Document ready handler
$(document).ready(function() {
    // Auto-initialize all tables with 'datatable' class
    $('.datatable').each(function() {
        const tableId = $(this).attr('id');
        if (!tableId) {
            const randomId = 'dataTable-' + Math.random().toString(36).substr(2, 9);
            $(this).attr('id', randomId);
            initDataTable(randomId);
        } else {
            initDataTable(tableId);
        }
    });
});
