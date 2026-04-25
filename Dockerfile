FROM php:8.2-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql \
    && a2enmod rewrite

RUN apt-get update && apt-get install -y --no-install-recommends \
        libcurl4-openssl-dev ca-certificates \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install curl

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html

# Render passes the port via $PORT — bind Apache to it.
ENV PORT=10000
RUN sed -ri 's!Listen 80!Listen ${PORT}!' /etc/apache2/ports.conf \
    && sed -ri 's!:80>!:${PORT}>!' /etc/apache2/sites-available/000-default.conf

EXPOSE 10000

CMD ["apache2-foreground"]
