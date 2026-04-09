#!/usr/bin/env bash
# ---------------------------------------------------------------------
# Copyright (C) 2024 DevPanel
# You can install any service here to support your project
# Please make sure you run apt update before install any packages
# Example:
# - apt-get update
# - apt-get install nano
#
# ----------------------------------------------------------------------
if [ -n "$DEBUG_SCRIPT" ]; then
    set -x
fi

# Install APT packages.
if ! command -v npm >/dev/null 2>&1; then
  apt-get update
  apt-get install -y jq nano npm
fi

# Enable AVIF support in GD extension if not already enabled.
if [ -z "$(php --ri gd | grep AVIF)" ]; then
  apt-get install -y libavif-dev
  docker-php-ext-configure gd --with-avif --with-freetype --with-jpeg --with-webp
  docker-php-ext-install gd
fi

PECL_UPDATED=false
# Install APCU extension. Bypass question about enabling internal debugging.
if ! php --ri apcu > /dev/null 2>&1; then
  $PECL_UPDATED || pecl update-channels && PECL_UPDATED=true
  pecl install apcu <<< ''
  echo 'extension=apcu.so' > /usr/local/etc/php/conf.d/apcu.ini
fi
# Install uploadprogress extension.
if ! php --ri uploadprogress > /dev/null 2>&1; then
  $PECL_UPDATED || pecl update-channels && PECL_UPDATED=true
  pecl install uploadprogress
  echo 'extension=uploadprogress.so' > /usr/local/etc/php/conf.d/uploadprogress.ini
fi
# Disable Xdebug if it's enabled, as it can interfere with performance and is
# not needed in production.
if [ -f /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ]; then
  rm -f /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && PECL_UPDATED=true
fi
# Enable JIT if not already enabled, as it can improve performance for Drupal.
if php --ri 'Zend OPcache' | grep 'opcache.enable => On' > /dev/null 2>&1; then
  echo 'opcache.jit=tracing' > /usr/local/etc/php/conf.d/opcache.ini \
    && PECL_UPDATED=true
fi
# Reload Apache if it's running.
if $PECL_UPDATED && /etc/init.d/apache2 status > /dev/null; then
  /etc/init.d/apache2 reload
fi
