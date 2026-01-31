FROM dunglas/frankenphp

# System deps (including build tools for pcntl)
RUN apt-get update && apt-get install -y \
    git curl unzip libzip-dev libpq-dev \
    supervisor \
    build-essential autoconf pkg-config \
    && apt-get install -y libicu-dev \
    && docker-php-ext-install \
    pdo pdo_pgsql zip intl \
    && (pecl install redis || \
        (cd /tmp && \
         git clone https://github.com/phpredis/phpredis.git && \
         cd phpredis && \
         phpize && \
         ./configure && \
         make -j$(nproc) && \
         make install && \
         docker-php-ext-enable redis)) \
    && docker-php-ext-enable redis

# Install and enable pcntl extension (required for Laravel Octane)
# Get full PHP version and download from php.net, then verify installation
RUN set -eux; \
    PHP_FULL_VERSION=$(php -r 'echo PHP_VERSION;'); \
    PHP_MAJOR_MINOR=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;'); \
    echo "PHP Version: ${PHP_FULL_VERSION}"; \
    cd /tmp; \
    curl -fsSL "https://www.php.net/distributions/php-${PHP_FULL_VERSION}.tar.xz" -o php.tar.xz || \
    (echo "Trying latest ${PHP_MAJOR_MINOR} version..." && \
    curl -fsSL "https://www.php.net/distributions/php-${PHP_MAJOR_MINOR}.tar.xz" -o php.tar.xz || \
    curl -fsSL "https://downloads.php.net/~derick/php-${PHP_FULL_VERSION}.tar.xz" -o php.tar.xz); \
    tar -xf php.tar.xz; \
    cd php-${PHP_FULL_VERSION}/ext/pcntl || cd php-${PHP_MAJOR_MINOR}*/ext/pcntl; \
    phpize; \
    ./configure --enable-pcntl; \
    make -j$(nproc); \
    make install; \
    docker-php-ext-enable pcntl; \
    php -m | grep -i pcntl || (echo "ERROR: pcntl not found in loaded modules" && exit 1); \
    php -r "if (!function_exists('pcntl_signal')) { echo 'ERROR: pcntl functions not available'; exit(1); } else { echo 'SUCCESS: pcntl is available'; }"; \
    rm -rf /tmp/php-* /tmp/php.tar.xz

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Supervisor
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Composer deps
COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-scripts

# App source
COPY . .

RUN composer dump-autoload --optimize

EXPOSE 8000
