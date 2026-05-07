#!/bin/sh
# Init script - runs on first start

sleep 2  # wait for DB to be ready

PGPASSWORD=tamalbank-password psql -h db -U tamalbank-user -d tamalbank-db -c "
SELECT tablename FROM pg_tables WHERE schemaname='public';
" | grep -q products || {
    echo "Creating tables..."
    PGPASSWORD=tamalbank-password psql -h db -U tamalbank-user -d tamalbank-db -f /docker-entrypoint-initdb.d/init.sql
}

echo "DB ready"