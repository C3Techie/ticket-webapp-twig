#!/bin/bash
# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Create necessary directories for Render
mkdir -p /tmp/data
mkdir -p cache

# Set proper permissions
chmod 755 cache
chmod -R 755 src/templates