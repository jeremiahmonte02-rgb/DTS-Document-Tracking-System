#!/bin/sh
set -e

echo "Starting deployment entrypoint..."

max_tries=5
count=1

while [ $count -le $max_tries ]; do
    echo "Running database migrations (Attempt $count of $max_tries)..."
    if php src/artisan migrate --force; then
        echo "✅ Migrations completed successfully."
        break
    fi
    echo "⚠️ Migration attempt $count failed. Retrying in 5 seconds..."
    sleep 5
    count=$((count + 1))
done

if [ $count -gt $max_tries ]; then
    echo "❌ Migrations failed after $max_tries attempts. Exiting to prevent broken deployment."
    exit 1
fi

echo "🚀 Starting web server..."
exec "$@"
