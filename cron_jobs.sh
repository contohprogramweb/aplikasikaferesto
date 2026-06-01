#!/bin/bash
# Cron Jobs for RestoPOS System
# Add these to crontab with: crontab -e

# Session cleanup - Run every hour
0 * * * * php /workspace/index.php cron/cleanup_sessions >> /workspace/logs/cron_sessions.log 2>&1

# Database backup - Run daily at 2 AM
0 2 * * * mysqldump -u [DB_USER] -p[DB_PASS] [DB_NAME] > /workspace/backups/db_$(date +\%Y\%m\%d_\%H\%M\%S).sql >> /workspace/logs/cron_backup.log 2>&1

# Log rotation - Run weekly on Sunday at 3 AM
0 3 * * 0 find /workspace/logs -name "*.log" -mtime +7 -delete >> /workspace/logs/cron_rotation.log 2>&1

# Receipt cleanup - Delete receipts older than 90 days monthly
0 4 1 * * find /workspace/uploads/receipts -name "*.pdf" -mtime +90 -delete >> /workspace/logs/cron_receipts.log 2>&1

# Cache cleanup - Run daily at 4 AM
0 4 * * * rm -rf /workspace/application/cache/* >> /workspace/logs/cron_cache.log 2>&1

# Temporary file cleanup - Run daily at 5 AM
0 5 * * * find /workspace/uploads/temp -type f -mtime +1 -delete >> /workspace/logs/cron_temp.log 2>&1
