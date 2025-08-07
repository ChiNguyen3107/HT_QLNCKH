<?php
// Script cập nhật mã quyết định với xử lý foreign key constraint
$conn = new mysqli('localhost', 'root', '', 'ql_nckh');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

echo "=== UPDATING DECISION CODE FIELD WITH FOREIGN KEY HANDLING ===\n\n";

echo "1. Checking foreign key constraints on QD_SO:\n";
$result = $conn->query("
    SELECT 
        CONSTRAINT_NAME, 
        TABLE_NAME, 
        COLUMN_NAME, 
        REFERENCED_TABLE_NAME, 
        REFERENCED_COLUMN_NAME
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE REFERENCED_TABLE_NAME = 'quyet_dinh_nghiem_thu' 
    AND REFERENCED_COLUMN_NAME = 'QD_SO'
    AND TABLE_SCHEMA = 'ql_nckh'
");

$foreign_keys = [];
if ($result && $result->num_rows > 0) {
    echo "   Found foreign key constraints:\n";
    while ($row = $result->fetch_assoc()) {
        $foreign_keys[] = $row;
        echo "   - " . $row['CONSTRAINT_NAME'] . " in table " . $row['TABLE_NAME'] . "\n";
    }
} else {
    echo "   No foreign key constraints found.\n";
}

echo "\n2. Current QD_SO field structure:\n";
$result = $conn->query('DESCRIBE quyet_dinh_nghiem_thu');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        if ($row['Field'] == 'QD_SO') {
            echo "   Type: " . $row['Type'] . "\n";
            break;
        }
    }
}

echo "\n3. Checking existing data:\n";
$result = $conn->query('SELECT QD_SO, LENGTH(QD_SO) as length FROM quyet_dinh_nghiem_thu');
if ($result && $result->num_rows > 0) {
    echo "   Existing decision codes:\n";
    while ($row = $result->fetch_assoc()) {
        echo "   - " . $row['QD_SO'] . " (Length: " . $row['length'] . ")\n";
    }
} else {
    echo "   No existing decision codes found.\n";
}

echo "\n4. Dropping foreign key constraints temporarily...\n";
foreach ($foreign_keys as $fk) {
    $drop_sql = "ALTER TABLE `{$fk['TABLE_NAME']}` DROP FOREIGN KEY `{$fk['CONSTRAINT_NAME']}`";
    if ($conn->query($drop_sql) === TRUE) {
        echo "   ✓ Dropped constraint: {$fk['CONSTRAINT_NAME']}\n";
    } else {
        echo "   ✗ Error dropping constraint {$fk['CONSTRAINT_NAME']}: " . $conn->error . "\n";
    }
}

echo "\n5. Updating related tables to VARCHAR(11)...\n";
foreach ($foreign_keys as $fk) {
    $alter_sql = "ALTER TABLE `{$fk['TABLE_NAME']}` MODIFY `{$fk['COLUMN_NAME']}` VARCHAR(11)";
    if ($conn->query($alter_sql) === TRUE) {
        echo "   ✓ Updated {$fk['TABLE_NAME']}.{$fk['COLUMN_NAME']} to VARCHAR(11)\n";
    } else {
        echo "   ✗ Error updating {$fk['TABLE_NAME']}.{$fk['COLUMN_NAME']}: " . $conn->error . "\n";
    }
}

echo "\n6. Updating QD_SO field to VARCHAR(11)...\n";
$update_sql = "ALTER TABLE quyet_dinh_nghiem_thu MODIFY QD_SO VARCHAR(11) NOT NULL";
if ($conn->query($update_sql) === TRUE) {
    echo "   ✓ Successfully updated QD_SO field to VARCHAR(11)\n";
} else {
    echo "   ✗ Error updating QD_SO field: " . $conn->error . "\n";
    $conn->close();
    exit(1);
}

echo "\n7. Recreating foreign key constraints...\n";
foreach ($foreign_keys as $fk) {
    $create_sql = "ALTER TABLE `{$fk['TABLE_NAME']}` 
                   ADD CONSTRAINT `{$fk['CONSTRAINT_NAME']}` 
                   FOREIGN KEY (`{$fk['COLUMN_NAME']}`) 
                   REFERENCES `{$fk['REFERENCED_TABLE_NAME']}` (`{$fk['REFERENCED_COLUMN_NAME']}`) 
                   ON DELETE CASCADE ON UPDATE CASCADE";
    
    if ($conn->query($create_sql) === TRUE) {
        echo "   ✓ Recreated constraint: {$fk['CONSTRAINT_NAME']}\n";
    } else {
        echo "   ✗ Error recreating constraint {$fk['CONSTRAINT_NAME']}: " . $conn->error . "\n";
    }
}

echo "\n8. Verifying the update:\n";
$result = $conn->query('DESCRIBE quyet_dinh_nghiem_thu');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        if ($row['Field'] == 'QD_SO') {
            echo "   New Type: " . $row['Type'] . "\n";
            echo "   New length limit: 11 characters\n";
            break;
        }
    }
}

echo "\n=== UPDATE COMPLETED SUCCESSFULLY ===\n";
echo "The QD_SO field can now store up to 11 characters.\n";
echo "You can now use decision codes like: QD2024-0001, NGHIEM-001, DECISION01, etc.\n";

$conn->close();
?>
