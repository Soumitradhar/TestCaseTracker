# TestFlow - Production Dockerfile for Render
# Supports PHP with Apache, MySQL, and Python for PDF generation

FROM php:8.1-apache

LABEL maintainer="TestFlow Team"

# Install system dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    python3 \
    python3-pip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zlib1g-dev \
    git \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Install Python reportlab library for PDF generation
# Debian-based images now block system pip installs unless explicitly allowed.
RUN python3 -m pip install --upgrade pip && \
    python3 -m pip install --break-system-packages --no-cache-dir reportlab

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache modules
RUN a2enmod rewrite

# Set Apache document root
ENV APACHE_DOCUMENT_ROOT=/var/www/html

# Copy application files
COPY . /var/www/html/

# Set working directory
WORKDIR /var/www/html

# Create necessary directories with proper permissions
RUN mkdir -p /var/www/html/pdf_uploads /var/www/html/uploads && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html && \
    chmod -R 777 /var/www/html/pdf_uploads /var/www/html/uploads

# Create Apache vhost configuration for clean URLs
RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html\n\
    <Directory /var/www/html>\n\
        Options Indexes FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
        RewriteEngine On\n\
        RewriteCond %{REQUEST_FILENAME} !-f\n\
        RewriteCond %{REQUEST_FILENAME} !-d\n\
    </Directory>\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Expose port 80
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/testflow/index.html || exit 1

# Start Apache in foreground
CMD ["apache2-foreground"]
