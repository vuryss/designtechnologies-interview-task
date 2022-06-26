FROM composer:2.3 AS composer
FROM php:8.1.7-alpine3.16

# Install basic cli tools like ps, vim, htop & others
ADD docker/scripts/install-basic-cli-tools.sh /root/
RUN chmod a+x /root/install-basic-cli-tools.sh && sh /root/install-basic-cli-tools.sh

# Install needed extensions
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && sync && \
    install-php-extensions \
        bcmath \
        opcache \
        apcu \
        intl \
        xdebug

# PHP configuration
ADD docker/config/php.ini /usr/local/etc/php/php.ini
ADD docker/config/xdebug.ini /usr/local/etc/php/conf.d/zz-xdebug.ini

# Composer
COPY --from=composer /usr/bin/composer /usr/bin/composer

# Symfony CLI
RUN mkdir -p /usr/local/bin \
  && curl -LsS https://github.com/symfony-cli/symfony-cli/releases/download/v5.4.11/symfony-cli_5.4.11_x86_64.apk -o /root/symfony-cli.apk \
  && apk add --allow-untrusted /root/symfony-cli.apk

# Ensure a non-root user and group with ID 1000 exists
COPY docker/scripts/ensure-non-root-user.sh /root/ensure-non-root-user.sh
RUN chmod a+x /root/ensure-non-root-user.sh && sh /root/ensure-non-root-user.sh

# Ensure application directory exists with correct permissions
RUN mkdir -p /app && chown -R user:user /app

USER 1000
