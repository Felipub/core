FROM composer:2.2.25 AS composer
FROM php:7.4.33-apache-bullseye
RUN rm -f /etc/apt/sources.list.d/* \
    && printf 'deb [check-valid-until=no] https://snapshot.debian.org/archive/debian/20240601T000000Z bullseye main\ndeb [check-valid-until=no] https://snapshot.debian.org/archive/debian-security/20240601T000000Z bullseye-security main\n' > /etc/apt/sources.list \
    && apt-get -o Acquire::Retries=3 update && apt-get install -y --no-install-recommends \
    libcurl4-openssl-dev libonig-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    libzip-dev libicu-dev libxml2-dev unzip git locales \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j2 curl mbstring gettext pdo_mysql mysqli gd zip intl soap bcmath \
    && echo 'en_GB.UTF-8 UTF-8' >> /etc/locale.gen && locale-gen \
    && a2enmod rewrite headers && rm -rf /var/lib/apt/lists/* \
    && mkdir -p /var/run/apache2 /var/lock/apache2 \
    && chown -R www-data:www-data /var/run/apache2 /var/lock/apache2 /var/log/apache2
COPY --from=composer /usr/bin/composer /usr/local/bin/composer
COPY php.ini /usr/local/etc/php/conf.d/regression.ini
COPY apache.conf /etc/apache2/conf-enabled/regression.conf
WORKDIR /var/www/html
