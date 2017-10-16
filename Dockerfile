FROM php:5-apache

RUN docker-php-ext-install mysql

#COPY src/ /var/www/html/
