# Backup & Recovery System Documentation
## Smart Restaurant POS - Berdasarkan SRS v4.0 Lampiran 8.6 dan 8.7

---

## 📁 STRUKTUR FILE

```
/workspace/
├── database/
│   ├── backup.php          # Script backup database & files
│   └── restore.php         # Script restore database
├── cron_jobs.sh            # Konfigurasi cron jobs
├── application/
│   └── logs/
│       ├── backup.log      # Log backup
│       └── restore.log     # Log restore
└── public/
    └── uploads/            # Direktori file uploads (yang dibackup)
```

---

## 🔧 BACKUP SCRIPT (`database/backup.php`)

### Fitur
- ✅ MySQL dump dengan gzip compression
- ✅ Backup direktori uploads/
- ✅ Retention policy: hapus file >7 hari
- ✅ Logging setiap backup

### Penggunaan

```bash
# Backup database saja
php /workspace/database/backup.php db

# Backup file uploads saja
php /workspace/database/backup.php files

# Backup database dan files (default)
php /workspace/database/backup.php all

# Hapus backup lama (>7 hari)
php /workspace/database/backup.php cleanup
```

### Output Format
- Database: `/backup/db_YYYYMMDD_HHMMSS.sql.gz`
- Files: `/backup/uploads_YYYYMMDD_HHMMSS.tar.gz`

### Konfigurasi

Edit bagian konfigurasi di `database/backup.php`:

```php
$db_config = [
    'host' => 'localhost',
    'port' => '3306',
    'database' => 'smart_restaurant_pos',
    'username' => 'root',
    'password' => '',      // Sesuaikan password
    'charset' => 'utf8mb4'
];

$backup_base_dir = '/backup';  // Lokasi backup
$retention_days = 7;           // Retention period
```

### Log File
Lokasi: `/workspace/application/logs/backup.log`

Contoh log:
```
[2025-01-15 02:00:01] ----------------------------------------
[2025-01-15 02:00:01] Backup Script Started - Action: db
[2025-01-15 02:00:01] === MEMULAI BACKUP DATABASE ===
[2025-01-15 02:00:01] Executing: mysqldump -hlocalhost -P3306 -uroot -p*** ...
[2025-01-15 02:00:15] SUCCESS: Backup database selesai
[2025-01-15 02:00:15] File: db_20250115_020001.sql.gz
[2025-01-15 02:00:15] Ukuran: 15.23 MB
[2025-01-15 02:00:15] Lokasi: /backup/db_20250115_020001.sql.gz
```

---

## 🔄 RECOVERY SCRIPT (`database/restore.php`)

### Fitur
- ✅ Extract file .sql.gz
- ✅ Restore ke database baru untuk verifikasi
- ✅ Swap database production (dengan downtime minimal)
- ✅ Logging recovery process
- ✅ Rollback otomatis jika gagal

### Penggunaan

```bash
# List backup tersedia
php /workspace/database/restore.php --list

# Restore dengan verifikasi (interactive)
php /workspace/database/restore.php /backup/db_20250115_020001.sql.gz

# Restore langsung ke production (tanpa konfirmasi)
php /workspace/database/restore.php /backup/db_20250115_020001.sql.gz --force

# Hanya verifikasi tanpa restore
php /workspace/database/restore.php /backup/db_20250115_020001.sql.gz --verify-only
```

### Proses Restore

1. **Extract** file `.sql.gz` ke temporary file
2. **Restore** ke database temp (`smart_restaurant_pos_restore_xxxxxxxx`)
3. **Verify** data integrity (cek tabel users, products, orders)
4. **Swap** databases:
   - Rename production → `smart_restaurant_pos_backup_YYYYMMDDHHmmss`
   - Rename verified → `smart_restaurant_pos`
5. **Cleanup** temporary files

### Safety Features
- ✅ Database lama dipreserve sebagai backup
- ✅ Rollback otomatis jika swap gagal
- ✅ Verifikasi sebelum swap production
- ✅ User confirmation sebelum swap (kecuali --force)

### Log File
Lokasi: `/workspace/application/logs/restore.log`

---

## ⏰ CRON JOBS CONFIGURATION

### Instalasi

```bash
# Option 1: Install langsung dari file
crontab /workspace/cron_jobs.sh

# Option 2: Copy manual
crontab -e
# Paste isi file cron_jobs.sh

# Verifikasi instalasi
crontab -l
```

### Jadwal Backup & Maintenance

| Job | Schedule | Command | Description |
|-----|----------|---------|-------------|
| **Database Backup** | Daily 02:00 | `backup.php db` | Backup database harian |
| **Files Backup** | Weekly Sun 03:00 | `backup.php files` | Backup uploads mingguan |
| **Backup Cleanup** | Daily 02:30 | `backup.php cleanup` | Hapus backup >7 hari |
| **Session Cleanup** | Hourly | `index.php cron cleanup_sessions` | Hapus expired sessions |
| **Log Rotation** | Daily 02:00 | `find ... -mtime +30 -delete` | Hapus log >30 hari |
| **Activity Archive** | Monthly 1st 03:30 | `index.php cron archive_logs` | Archive activity logs >90 hari |
| **Archive Cleanup** | Monthly 1st 04:00 | `find ... -mtime +730 -delete` | Hapus archive >2 tahun |
| **Receipt Cleanup** | Monthly 1st 05:00 | `find ... -mtime +365 -delete` | Hapus receipt PDF >1 tahun |
| **Temp Cleanup** | Daily 05:00 | `find ... -mtime +1 -delete` | Hapus temp files >1 hari |
| **Cache Cleanup** | Daily 04:00 | `rm -rf cache/*` | Clear application cache |
| **DB Optimize** | Weekly Sun 04:00 | `mysql ... OPTIMIZE TABLES` | Optimize database tables |
| **Disk Check** | Daily 06:00 | `df -h` | Check disk space |

### Konfigurasi Cron

Edit `cron_jobs.sh` sesuai environment:

```bash
PROJECT_ROOT="/workspace"        # Path project Anda
PHP_BIN="/usr/bin/php"           # Path PHP binary
BACKUP_DIR="/backup"             # Lokasi backup
LOG_DIR="${PROJECT_ROOT}/application/logs"

DB_USER="root"                   # Database username
DB_PASS=""                       # Database password
DB_NAME="smart_restaurant_pos"   # Database name
DB_HOST="localhost"              # Database host
```

---

## 🗑️ RETENTION POLICY

### Data Cleanup Schedule

| Data Type | Retention Period | Cleanup Method | Schedule |
|-----------|------------------|----------------|----------|
| `customer_sessions` | 1 hour | `DELETE WHERE expires_at < NOW() - INTERVAL 1 HOUR` | Hourly |
| `activity_logs` | 90 days | `MOVE TO archive WHERE created_at < NOW() - INTERVAL 90 DAY` | Monthly |
| `activity_logs_archive` | 2 years | `DELETE WHERE created_at < NOW() - INTERVAL 2 YEAR` | Monthly |
| Receipt PDFs | 1 year | File deletion | Monthly |
| Backup files | 7 days | File deletion | Daily |
| Temp files | 1 day | File deletion | Daily |
| Application logs | 30 days | File deletion | Daily |

### SQL Queries untuk Manual Cleanup

```sql
-- Session cleanup
DELETE FROM customer_sessions WHERE expires_at < NOW() - INTERVAL 1 HOUR;

-- Activity log archive
INSERT INTO activity_logs_archive 
SELECT * FROM activity_logs WHERE created_at < NOW() - INTERVAL 90 DAY;

DELETE FROM activity_logs WHERE created_at < NOW() - INTERVAL 90 DAY;

-- Archive cleanup
DELETE FROM activity_logs_archive WHERE created_at < NOW() - INTERVAL 2 YEAR;

-- Receipt PDF cleanup (via filesystem)
-- DELETE FROM receipts WHERE created_at < NOW() - INTERVAL 1 YEAR;
```

---

## 🚨 DISASTER RECOVERY PROCEDURE

### Scenario 1: Database Corruption

```bash
# 1. List available backups
php /workspace/database/restore.php --list

# 2. Restore latest backup with verification
php /workspace/database/restore.php /backup/db_20250115_020001.sql.gz

# 3. If successful, confirm the swap when prompted
# 4. Verify application functionality
# 5. Manually drop old backup database after confirmation
```

### Scenario 2: Complete System Failure

```bash
# 1. Restore database from backup
php /workspace/database/restore.php /backup/db_YYYYMMDD_HHMMSS.sql.gz --force

# 2. Restore uploaded files
cd /backup
tar -xzf uploads_YYYYMMDD_HHMMSS.tar.gz -C /workspace/public/

# 3. Verify application
curl http://your-domain.com/admin

# 4. Check logs for errors
tail -f /workspace/application/logs/*.log
```

### Scenario 3: Accidental Data Deletion

```bash
# 1. Stop application (optional, to prevent further changes)
# systemctl stop nginx php-fpm

# 2. Restore to temp database for verification
php /workspace/database/restore.php /backup/db_YYYYMMDD_HHMMSS.sql.gz --verify-only

# 3. Verify recovered data
mysql -u root -p smart_restaurant_pos_restore_xxxxxxxx -e "SELECT COUNT(*) FROM orders;"

# 4. If OK, proceed with full restore
php /workspace/database/restore.php /backup/db_YYYYMMDD_HHMMSS.sql.gz --force

# 5. Restart application
# systemctl start nginx php-fpm
```

---

## 📊 MONITORING & ALERTING

### Check Backup Status

```bash
# Check latest backup
ls -lht /backup/*.sql.gz | head -1

# Check backup size
du -sh /backup/

# Check backup logs
tail -50 /workspace/application/logs/backup.log

# Check for errors in cron logs
grep -i error /workspace/application/logs/cron_*.log
```

### Health Check Script

```bash
#!/bin/bash
# backup_health_check.sh

BACKUP_DIR="/backup"
LOG_FILE="/workspace/application/logs/backup_health.log"

# Check if backup exists today
TODAY=$(date +%Y%m%d)
if ls ${BACKUP_DIR}/db_${TODAY}*.sql.gz 1> /dev/null 2>&1; then
    echo "[$(date)] ✓ Database backup OK" >> $LOG_FILE
else
    echo "[$(date)] ✗ Database backup MISSING" >> $LOG_FILE
    # Send alert email/notification here
fi

# Check backup directory size
SIZE=$(du -sm ${BACKUP_DIR} | cut -f1)
if [ $SIZE -gt 10000 ]; then  # > 10GB
    echo "[$(date)] ⚠ Backup directory large: ${SIZE}MB" >> $LOG_FILE
fi
```

---

## 🔐 SECURITY CONSIDERATIONS

### Best Practices

1. **Database Credentials**
   - Gunakan dedicated backup user dengan minimal privileges
   - Simpan credentials di file terpisah dengan permission 600

2. **Backup Encryption**
   ```bash
   # Encrypt backup dengan GPG
   mysqldump ... | gzip | gpg --encrypt --recipient backup@company.com > backup.sql.gz.gpg
   ```

3. **Off-site Backup**
   ```bash
   # Sync ke remote server
   rsync -avz /backup/ user@remote-server:/offsite-backup/
   
   # Atau upload ke cloud storage
   aws s3 cp /backup/ s3://your-bucket/backups/ --recursive
   ```

4. **Access Control**
   ```bash
   # Restrict access to backup scripts
   chown root:root /workspace/database/backup.php
   chmod 700 /workspace/database/backup.php
   
   # Restrict access to backup directory
   chown root:root /backup
   chmod 700 /backup
   ```

---

## 📝 TROUBLESHOOTING

### Common Issues

**Issue**: Backup gagal dengan error "mysqldump: command not found"
```bash
# Solution: Install mysql-client
apt-get install mysql-client
# atau
yum install mysql
```

**Issue**: Permission denied saat write ke /backup
```bash
# Solution: Fix permissions
mkdir -p /backup
chown www-data:www-data /backup
chmod 755 /backup
```

**Issue**: Backup file terlalu besar
```bash
# Solution: Exclude unnecessary tables
mysqldump --ignore-table=db.sessions --ignore-table=db.cache ...
```

**Issue**: Restore gagal karena database locked
```bash
# Solution: Kill long-running queries
mysql -u root -p -e "SHOW PROCESSLIST;"
mysql -u root -p -e "KILL <process_id>;"
```

---

## 📞 SUPPORT

Untuk pertanyaan atau issue terkait backup & recovery system:

1. Check log files di `/workspace/application/logs/`
2. Review cron job execution: `grep CRON /var/log/syslog`
3. Verify disk space: `df -h /backup`
4. Test restore procedure secara berkala di staging environment

---

*Dokumentasi ini dibuat berdasarkan SRS v4.0 Lampiran 8.6 dan 8.7*
*Last updated: 2025-01-15*
