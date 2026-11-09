# OpenFlix Global - High-Performance PHP & SQLite Streaming Container
FROM php:8.3-cli-alpine

WORKDIR /app

# Copy application files
COPY . /app

# Configure permissions for SQLite database read/write
RUN chmod 777 /app && \
    touch /app/database.sqlite && \
    chmod 666 /app/database.sqlite

# Default PORT for cloud environments (Render, Railway, Fly.io, Cloud Run)
ENV PORT=8080
EXPOSE 8080

# Start PHP built-in web server binding to dynamic cloud port
CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8080} -t /app"]
