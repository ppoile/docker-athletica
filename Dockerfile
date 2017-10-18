FROM php:5-apache

RUN docker-php-ext-install mysql

COPY config/php.ini /usr/local/etc/php/conf.d/

#COPY src/ /var/www/html/
