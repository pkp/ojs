# Backup and Recovery Guide

This document outlines the backup and recovery procedures for MOJS.

## Backup Strategies

### 1. Database Backups
```bash
# Example database backup script
#!/bin/bash

# Configuration
DB_NAME="mojs"
BACKUP_DIR="/backups/database"
DATE=$(date +%Y-%m-%d_%H-%M-%S)

# Create backup
pg_dump -U postgres -F c -b -v -f "$BACKUP_DIR/$DB_NAME-$DATE.dump" $DB_NAME

# Compress backup
gzip "$BACKUP_DIR/$DB_NAME-$DATE.dump"

# Upload to cloud storage
aws s3 cp "$BACKUP_DIR/$DB_NAME-$DATE.dump.gz" s3://mojs-backups/database/

# Cleanup old backups (keep last 7 days)
find $BACKUP_DIR -type f -mtime +7 -delete
```

### 2. File System Backups
```bash
# Example file system backup script
#!/bin/bash

# Configuration
BACKUP_DIR="/backups/filesystem"
DATA_DIR="/var/www/mojs"
DATE=$(date +%Y-%m-%d_%H-%M-%S)

# Create backup
tar -czf "$BACKUP_DIR/mojs-files-$DATE.tar.gz" $DATA_DIR

# Upload to cloud storage
aws s3 cp "$BACKUP_DIR/mojs-files-$DATE.tar.gz" s3://mojs-backups/filesystem/

# Cleanup old backups (keep last 7 days)
find $BACKUP_DIR -type f -mtime +7 -delete
```

## Recovery Procedures

### 1. Database Recovery
```bash
# Example database recovery script
#!/bin/bash

# Configuration
DB_NAME="mojs"
BACKUP_FILE="$1"  # Backup file to restore

# Stop services
systemctl stop mojs

# Restore database
gunzip -c $BACKUP_FILE | pg_restore -U postgres -d $DB_NAME -v

# Start services
systemctl start mojs
```

### 2. File System Recovery
```bash
# Example file system recovery script
#!/bin/bash

# Configuration
BACKUP_FILE="$1"  # Backup file to restore
RESTORE_DIR="/var/www/mojs"

# Stop services
systemctl stop mojs

# Restore files
tar -xzf $BACKUP_FILE -C $RESTORE_DIR

# Set permissions
chown -R www-data:www-data $RESTORE_DIR
chmod -R 755 $RESTORE_DIR

# Start services
systemctl start mojs
```

## Backup Schedule

### 1. Automated Backups
```yaml
# Example cron schedule
# Database backups (daily at 2 AM)
0 2 * * * /scripts/backup-database.sh

# File system backups (weekly on Sunday at 3 AM)
0 3 * * 0 /scripts/backup-filesystem.sh
```

### 2. Manual Backups
```bash
# Manual backup commands
./scripts/backup-database.sh
./scripts/backup-filesystem.sh
```

## Backup Verification

### 1. Integrity Checks
```bash
# Example verification script
#!/bin/bash

# Configuration
BACKUP_FILE="$1"

# Verify backup integrity
if gunzip -t $BACKUP_FILE; then
    echo "Backup integrity check passed"
else
    echo "Backup integrity check failed"
    exit 1
fi
```

### 2. Recovery Testing
```bash
# Example recovery test script
#!/bin/bash

# Configuration
TEST_DB="mojs_test"
BACKUP_FILE="$1"

# Create test database
createdb -U postgres $TEST_DB

# Restore backup to test database
gunzip -c $BACKUP_FILE | pg_restore -U postgres -d $TEST_DB -v

# Verify data
psql -U postgres -d $TEST_DB -c "SELECT COUNT(*) FROM articles;"

# Cleanup
dropdb -U postgres $TEST_DB
```

## Disaster Recovery

### 1. Full System Recovery
```bash
# Example full recovery script
#!/bin/bash

# Configuration
BACKUP_DATE="$1"  # Date of backup to restore

# Stop all services
systemctl stop mojs
systemctl stop postgresql
systemctl stop nginx

# Restore database
./scripts/restore-database.sh "/backups/database/mojs-$BACKUP_DATE.dump.gz"

# Restore files
./scripts/restore-filesystem.sh "/backups/filesystem/mojs-files-$BACKUP_DATE.tar.gz"

# Start services
systemctl start postgresql
systemctl start nginx
systemctl start mojs
```

### 2. Partial Recovery
```bash
# Example partial recovery script
#!/bin/bash

# Configuration
BACKUP_DATE="$1"
TABLES="$2"  # Comma-separated list of tables

# Stop application
systemctl stop mojs

# Restore specific tables
for table in $(echo $TABLES | tr ',' ' '); do
    pg_restore -U postgres -d mojs -t $table "/backups/database/mojs-$BACKUP_DATE.dump"
done

# Start application
systemctl start mojs
```

## Backup Storage

### 1. Local Storage
```yaml
# Example local storage configuration
backup:
  local:
    path: /backups
    retention: 7d
    compression: true
```

### 2. Cloud Storage
```yaml
# Example cloud storage configuration
backup:
  cloud:
    provider: aws
    bucket: mojs-backups
    region: us-east-1
    retention: 30d
```

## Monitoring and Alerts

### 1. Backup Monitoring
```yaml
# Example monitoring configuration
monitoring:
  backups:
    enabled: true
    check_interval: 24h
    alert_threshold: 48h
```

### 2. Alert Configuration
```yaml
# Example alert configuration
alerts:
  backup_failure:
    enabled: true
    channels:
      - email
      - slack
    recipients:
      - admin@example.com
```

## Documentation

### 1. Backup Procedures
- Backup schedule
- Storage locations
- Retention policies
- Verification procedures

### 2. Recovery Procedures
- Recovery steps
- Testing procedures
- Contact information
- Emergency procedures 