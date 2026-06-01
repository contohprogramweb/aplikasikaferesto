<?php
/**
 * Migration Helper Script untuk Smart Restaurant POS
 * 
 * Cara penggunaan:
 *   php database/migrate.php           # Jalankan semua migrasi yang belum applied
 *   php database/migrate.php --status  # Lihat status migrasi
 *   php database/migrate.php --rollback 003  # Rollback ke versi tertentu
 * 
 * Berdasarkan SRS v4.0 NFR-MAI-07
 */

// Konfigurasi database
$db_config = [
    'host' => 'localhost',
    'port' => '3306',
    'database' => 'smart_restaurant_pos',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4'
];

// Direktori migrasi
$migrations_dir = __DIR__ . '/migrations';

// ============================================================================
// Database Connection
// ============================================================================

function get_db_connection($config) {
    try {
        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        return new PDO($dsn, $config['username'], $config['password'], $options);
    } catch (PDOException $e) {
        // Jika database belum ada, coba connect tanpa dbname
        if (strpos($e->getMessage(), 'Unknown database') !== false) {
            $dsn = "mysql:host={$config['host']};port={$config['port']};charset={$config['charset']}";
            $pdo = new PDO($dsn, $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$config['database']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
            return new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        }
        throw $e;
    }
}

// ============================================================================
// Migration Table Management
// ============================================================================

function create_migrations_table($pdo) {
    $sql = "CREATE TABLE IF NOT EXISTS `migrations` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `version` VARCHAR(50) NOT NULL,
        `filename` VARCHAR(255) NOT NULL,
        `applied_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `checksum` VARCHAR(64) DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uk_migrations_version` (`version`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
}

function get_applied_migrations($pdo) {
    $stmt = $pdo->query("SELECT version, filename, applied_at FROM migrations ORDER BY version ASC");
    return $stmt->fetchAll();
}

function is_migration_applied($pdo, $version) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM migrations WHERE version = ?");
    $stmt->execute([$version]);
    return $stmt->fetchColumn() > 0;
}

function record_migration($pdo, $version, $filename) {
    $stmt = $pdo->prepare("INSERT INTO migrations (version, filename, applied_at) VALUES (?, ?, NOW())");
    $stmt->execute([$version, $filename]);
}

function remove_migration_record($pdo, $version) {
    $stmt = $pdo->prepare("DELETE FROM migrations WHERE version = ?");
    $stmt->execute([$version]);
}

// ============================================================================
// Migration File Management
// ============================================================================

function get_migration_files($dir) {
    if (!is_dir($dir)) {
        return [];
    }
    
    $files = scandir($dir);
    $migrations = [];
    
    foreach ($files as $file) {
        if (preg_match('/^(\d+)_(.+)\.sql$/', $file, $matches)) {
            $version = $matches[1];
            $migrations[$version] = [
                'version' => $version,
                'filename' => $file,
                'path' => $dir . '/' . $file,
                'name' => $matches[2]
            ];
        }
    }
    
    ksort($migrations);
    return $migrations;
}

function calculate_file_checksum($filepath) {
    if (file_exists($filepath)) {
        return hash_file('sha256', $filepath);
    }
    return null;
}

// ============================================================================
// Migration Execution
// ============================================================================

function execute_migration($pdo, $migration) {
    $filepath = $migration['path'];
    
    if (!file_exists($filepath)) {
        echo "ERROR: File tidak ditemukan: {$filepath}\n";
        return false;
    }
    
    $sql = file_get_contents($filepath);
    
    // Split SQL statements (handle DELIMITER)
    $statements = split_sql_statements($sql);
    
    echo "Menjalankan migrasi: {$migration['version']} - {$migration['name']}\n";
    
    try {
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement) && !preg_match('/^(--|#|\/\*)/', $statement)) {
                $pdo->exec($statement);
            }
        }
        
        // Record migration
        record_migration($pdo, $migration['version'], $migration['filename']);
        
        echo "✓ Migrasi {$migration['version']} berhasil diterapkan\n";
        return true;
    } catch (PDOException $e) {
        echo "✗ ERROR pada migrasi {$migration['version']}: " . $e->getMessage() . "\n";
        return false;
    }
}

function split_sql_statements($sql) {
    $statements = [];
    $current_statement = '';
    $delimiter = ';';
    $in_delimiter_block = false;
    
    $lines = explode("\n", $sql);
    
    foreach ($lines as $line) {
        $trimmed_line = trim($line);
        
        // Handle DELIMITER change
        if (preg_match('/^DELIMITER\s+(\S+)/i', $trimmed_line, $matches)) {
            if ($in_delimiter_block) {
                // End of delimiter block
                if (!empty(trim($current_statement))) {
                    $statements[] = $current_statement;
                }
                $current_statement = '';
                $in_delimiter_block = false;
                $delimiter = ';';
            } else {
                // Start of delimiter block
                $delimiter = $matches[1];
                $in_delimiter_block = true;
            }
            continue;
        }
        
        if ($in_delimiter_block) {
            $current_statement .= $line . "\n";
        } else {
            $current_statement .= $line . "\n";
            
            // Check for standard delimiter
            if (substr(rtrim($trimmed_line), -1) === ';') {
                $statements[] = $current_statement;
                $current_statement = '';
            }
        }
    }
    
    // Add remaining statement
    if (!empty(trim($current_statement))) {
        $statements[] = $current_statement;
    }
    
    return $statements;
}

// ============================================================================
// Commands
// ============================================================================

function cmd_migrate($pdo, $migrations_dir) {
    create_migrations_table($pdo);
    
    $available = get_migration_files($migrations_dir);
    $applied = get_applied_migrations($pdo);
    $applied_versions = array_column($applied, 'version');
    
    $pending = array_filter($available, function($m) use ($applied_versions) {
        return !in_array($m['version'], $applied_versions);
    });
    
    if (empty($pending)) {
        echo "Tidak ada migrasi yang perlu dijalankan. Database sudah up-to-date.\n";
        return;
    }
    
    echo "Ditemukan " . count($pending) . " migrasi yang belum diterapkan:\n\n";
    
    $success_count = 0;
    foreach ($pending as $migration) {
        if (execute_migration($pdo, $migration)) {
            $success_count++;
        } else {
            echo "\n⚠ Migrasi dihentikan karena error.\n";
            break;
        }
    }
    
    echo "\nSelesai: {$success_count}/" . count($pending) . " migrasi berhasil diterapkan.\n";
}

function cmd_status($pdo, $migrations_dir) {
    create_migrations_table($pdo);
    
    $available = get_migration_files($migrations_dir);
    $applied = get_applied_migrations($pdo);
    
    $applied_map = [];
    foreach ($applied as $a) {
        $applied_map[$a['version']] = $a;
    }
    
    echo "\n=== Migration Status ===\n\n";
    printf("%-10s | %-30s | %-10s | %s\n", "Version", "Filename", "Status", "Applied At");
    str_repeat("-", 80) . "\n";
    
    foreach ($available as $migration) {
        $version = $migration['version'];
        $filename = $migration['filename'];
        
        if (isset($applied_map[$version])) {
            $status = 'Applied';
            $applied_at = $applied_map[$version]['applied_at'];
        } else {
            $status = 'Pending';
            $applied_at = '-';
        }
        
        printf("%-10s | %-30s | %-10s | %s\n", $version, $filename, $status, $applied_at);
    }
    
    echo "\n";
}

function cmd_rollback($pdo, $target_version) {
    $applied = get_applied_migrations($pdo);
    
    if (empty($applied)) {
        echo "Tidak ada migrasi yang bisa di-rollback.\n";
        return;
    }
    
    // Sort applied migrations by version descending
    usort($applied, function($a, $b) {
        return strcmp($b['version'], $a['version']);
    });
    
    echo "Rolling back migrasi...\n";
    
    foreach ($applied as $migration) {
        if ($migration['version'] <= $target_version) {
            break;
        }
        
        echo "Rolling back: {$migration['version']} - {$migration['filename']}\n";
        remove_migration_record($pdo, $migration['version']);
        echo "✓ Rolled back {$migration['version']}\n";
    }
    
    echo "Rollback selesai.\n";
}

// ============================================================================
// Main Execution
// ============================================================================

echo "========================================\n";
echo "Smart Restaurant POS - Migration Tool\n";
echo "========================================\n\n";

try {
    $pdo = get_db_connection($db_config);
    echo "Terhubung ke database: {$db_config['database']}\n\n";
    
    $command = $argv[1] ?? 'migrate';
    
    switch ($command) {
        case 'migrate':
        case 'up':
            cmd_migrate($pdo, $migrations_dir);
            break;
            
        case 'status':
            cmd_status($pdo, $migrations_dir);
            break;
            
        case 'rollback':
        case 'down':
            $target = $argv[2] ?? '000';
            cmd_rollback($pdo, $target);
            break;
            
        case '--help':
        case '-h':
            echo "Usage:\n";
            echo "  php migrate.php           # Run all pending migrations\n";
            echo "  php migrate.php --status  # Show migration status\n";
            echo "  php migrate.php --rollback [version]  # Rollback to specific version\n";
            echo "  php migrate.php --help    # Show this help\n";
            break;
            
        default:
            echo "Command tidak dikenali: {$command}\n";
            echo "Gunakan 'php migrate.php --help' untuk bantuan.\n";
    }
    
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\nDone.\n";
