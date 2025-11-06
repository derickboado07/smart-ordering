<?php
// Safe migration: add foreign keys in REGISTERDB without breaking functionality.
// Usage: run this once from CLI (php add_registerdb_foreign_keys.php) or via browser.

include 'connect.php'; // provides $conn

function table_exists($conn, $table) {
    $res = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($table) . "'");
    return $res && $res->num_rows > 0;
}

function column_exists($conn, $table, $column) {
    $res = $conn->query("SHOW COLUMNS FROM `" . $conn->real_escape_string($table) . "` LIKE '" . $conn->real_escape_string($column) . "'");
    return $res && $res->num_rows > 0;
}

function fk_exists($conn, $table, $fk_name) {
    $db = $conn->real_escape_string($conn->query("SELECT DATABASE()")->fetch_row()[0]);
    $sql = "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA='" . $db . "' AND TABLE_NAME='" . $conn->real_escape_string($table) . "' AND CONSTRAINT_NAME='" . $conn->real_escape_string($fk_name) . "' AND CONSTRAINT_TYPE='FOREIGN KEY'";
    $res = $conn->query($sql);
    return $res && $res->num_rows > 0;
}

function make_column_nullable($conn, $table, $column) {
    $res = $conn->query("SHOW COLUMNS FROM `" . $conn->real_escape_string($table) . "` LIKE '" . $conn->real_escape_string($column) . "'");
    if (!($res && $res->num_rows > 0)) return false;
    $row = $res->fetch_assoc();
    $type = $row['Type'];
    $null = $row['Null'];
    if (strtoupper($null) === 'YES') {
        echo "    - Column is already nullable.\n";
        return true;
    }
    // Keep type and make NULLable
    $sql = "ALTER TABLE `" . $conn->real_escape_string($table) . "` MODIFY `" . $conn->real_escape_string($column) . "` " . $type . " NULL";
    return $conn->query($sql);
}

$fks = [
    // fk_name => [table, column, ref_table, ref_column]
    'fk_admin_notification_admin' => ['admin_notification','admin_id','admins','admin_id'],
    'fk_admin_notification_user'  => ['admin_notification','user_id','users','user_id'],
];

foreach ($fks as $fk_name => $info) {
    list($table, $column, $ref_table, $ref_column) = $info;

    echo "Checking $table.$column -> $ref_table.$ref_column ...\n";

    if (!table_exists($conn, $table)) {
        echo "  - Table $table does not exist. Skipping.\n";
        continue;
    }

    if (!table_exists($conn, $ref_table)) {
        echo "  - Referenced table $ref_table does not exist. Skipping.\n";
        continue;
    }

    if (!column_exists($conn, $table, $column)) {
        echo "  - Column $column not found in $table. Skipping.\n";
        continue;
    }

    if (!column_exists($conn, $ref_table, $ref_column)) {
        echo "  - Referenced column $ref_column not found in $ref_table. Skipping.\n";
        continue;
    }

    if (fk_exists($conn, $table, $fk_name)) {
        echo "  - Foreign key $fk_name already exists. Skipping.\n";
        continue;
    }

    // Ensure column is nullable so ON DELETE SET NULL won't fail
    echo "  - Ensuring $table.$column is nullable...\n";
    if (!make_column_nullable($conn, $table, $column)) {
        echo "    ! Could not alter column to NULL (it's possible the type is unsupported). Attempting to add FK anyway.\n";
    }

    // Check for invalid data before adding FK
    $check_sql = "SELECT COUNT(*) as invalid_count FROM `" . $conn->real_escape_string($table) . "` WHERE `" . $conn->real_escape_string($column) . "` IS NOT NULL AND `" . $conn->real_escape_string($column) . "` NOT IN (SELECT `" . $conn->real_escape_string($ref_column) . "` FROM `" . $conn->real_escape_string($ref_table) . "`)";
    $check_res = $conn->query($check_sql);
    if ($check_res) {
        $invalid_count = $check_res->fetch_assoc()['invalid_count'];
        if ($invalid_count > 0) {
            echo "    ! Found $invalid_count invalid references in $table.$column. Cleaning by setting to NULL...\n";
            $clean_sql = "UPDATE `" . $conn->real_escape_string($table) . "` SET `" . $conn->real_escape_string($column) . "` = NULL WHERE `" . $conn->real_escape_string($column) . "` IS NOT NULL AND `" . $conn->real_escape_string($column) . "` NOT IN (SELECT `" . $conn->real_escape_string($ref_column) . "` FROM `" . $conn->real_escape_string($ref_table) . "`)";
            if ($conn->query($clean_sql)) {
                echo "      ✓ Cleaned $invalid_count invalid references.\n";
            } else {
                echo "      ✗ Failed to clean: " . $conn->error . "\n";
                continue;
            }
        }
    }

    // Add index if not exists
    $idx_name = 'idx_' . $table . '_' . $column;
    $resIdx = $conn->query("SHOW INDEX FROM `" . $conn->real_escape_string($table) . "` WHERE Column_name='" . $conn->real_escape_string($column) . "'");
    if (!($resIdx && $resIdx->num_rows > 0)) {
        echo "  - Adding index $idx_name on $table($column)...\n";
        $conn->query("ALTER TABLE `" . $conn->real_escape_string($table) . "` ADD INDEX `" . $conn->real_escape_string($idx_name) . "` (`" . $conn->real_escape_string($column) . "`)");
    }

    // Finally add FK with ON DELETE SET NULL to avoid deleting data unexpectedly
    $sql = "ALTER TABLE `" . $conn->real_escape_string($table) . "` ADD CONSTRAINT `" . $conn->real_escape_string($fk_name) . "` FOREIGN KEY (`" . $conn->real_escape_string($column) . "`) REFERENCES `" . $conn->real_escape_string($ref_table) . "`(`" . $conn->real_escape_string($ref_column) . "`) ON DELETE CASCADE ON UPDATE CASCADE";
    echo "  - Adding foreign key $fk_name...\n";
    if ($conn->query($sql) === TRUE) {
        echo "    ✓ Foreign key $fk_name added.\n";
    } else {
        echo "    ✗ Failed to add FK $fk_name: " . $conn->error . "\n";
    }
}

echo "Done. Review output above for any skipped items or errors.\n";

?>
