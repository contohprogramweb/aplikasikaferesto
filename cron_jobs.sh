#!/bin/bash
# ============================================================================
# Cron Jobs Configuration for Smart Restaurant POS
# ============================================================================
# Berdasarkan SRS v4.0 Lampiran 8.6 dan 8.7
# 
# Instalasi:
#   1. Edit path project sesuai lokasi: PROJECT_ROOT="/workspace"
#   2. Install ke crontab: crontab /workspace/cron_jobs.sh
#   3. Atau copy isi file ini ke crontab: crontab -e
#
# Verifikasi: crontab -l
# ============================================================================

# Set environment variables
SHELL=/bin/bash
PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin
MAILTO=""

# Project Configuration - SESUAIKAN PATH INI
PROJECT_ROOT="/workspace"
PHP_BIN="/usr/bin/php"
BACKUP_DIR="/backup"
LOG_DIR="${PROJECT_ROOT}/application/logs"

# Database Configuration - SESUAIKAN DENGAN KONFIGURASI ANDA
DB_USER="root"
DB_PASS=""
DB_NAME="smart_restaurant_pos"
DB_HOST="localhost"
DB_PORT="3306"

# ============================================================================
# BACKUP JOBS
# ============================================================================

# Database backup harian jam 2 pagi
0 2 * * * ${PHP_BIN} ${PROJECT_ROOT}/database/backup.php db >> ${LOG_DIR}/cron_backup_db.log 2>&1

# File upload backup mingguan (Minggu jam 3 pagi)
0 3 * * 0 ${PHP_BIN} ${PROJECT_ROOT}/database/backup.php files >> ${LOG_DIR}/cron_backup_files.log 2>&1

# Backup cleanup (hapus backup >7 hari) - harian jam 2:30 pagi
30 2 * * * ${PHP_BIN} ${PROJECT_ROOT}/database/backup.php cleanup >> ${LOG_DIR}/cron_backup_cleanup.log 2>&1

# ============================================================================
# SESSION CLEANUP
# ============================================================================

# Session cleanup setiap jam
0 * * * * ${PHP_BIN} ${PROJECT_ROOT}/index.php cron cleanup_sessions >> ${LOG_DIR}/cron_sessions.log 2>&1

# ============================================================================
# LOG MANAGEMENT
# ============================================================================

# Log rotation harian - hapus log PHP >30 hari
0 2 * * * find ${LOG_DIR}/ -name "*.php" -mtime +30 -delete >> ${LOG_DIR}/cron_log_rotation.log 2>&1

# Activity log archive (90 hari) - bulanan tanggal 1 jam 3:30 pagi
0 3 1 * * ${PHP_BIN} ${PROJECT_ROOT}/index.php cron archive_logs >> ${LOG_DIR}/cron_archive_logs.log 2>&1

# Cleanup activity logs archive >2 tahun - bulanan tanggal 1 jam 4 pagi
0 4 1 * * find ${LOG_DIR}/ -name "*archive*" -mtime +730 -delete >> ${LOG_DIR}/cron_archive_cleanup.log 2>&1

# ============================================================================
# RECEIPT & FILE CLEANUP
# ============================================================================

# Receipt PDF cleanup (>1 tahun) - bulanan tanggal 1 jam 5 pagi
0 5 1 * * find ${PROJECT_ROOT}/public/uploads/receipts -name "*.pdf" -mtime +365 -delete >> ${LOG_DIR}/cron_receipts.log 2>&1

# Temporary file cleanup (>1 hari) - harian jam 5 pagi
0 5 * * * find ${PROJECT_ROOT}/public/uploads/temp -type f -mtime +1 -delete >> ${LOG_DIR}/cron_temp.log 2>&1

# Cache cleanup - harian jam 4 pagi
0 4 * * * rm -rf ${PROJECT_ROOT}/application/cache/* >> ${LOG_DIR}/cron_cache.log 2>&1

# ============================================================================
# MAINTENANCE
# ============================================================================

# Optimize database tables - mingguan Minggu jam 4 pagi
0 4 * * 0 mysql -u${DB_USER} -p${DB_PASS} -h${DB_HOST} ${DB_NAME} -e "OPTIMIZE TABLES" >> ${LOG_DIR}/cron_optimize.log 2>&1

# Check disk space - harian jam 6 pagi
0 6 * * * df -h | grep -v tmpfs >> ${LOG_DIR}/cron_disk.log 2>&1
