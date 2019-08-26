FROM php:5-apache

RUN apt-get update \
  && apt-get install -y libsmbclient-dev \
  && pecl install smbclient \
  && docker-php-ext-install mysql \
  && docker-php-ext-enable smbclient

COPY config/php.ini /usr/local/etc/php/conf.d/

COPY src/ /var/www/html/

# make temporary directory to store results to be uploaded
RUN mkdir -p /var/www/html/athletica/tmp \
  && chmod a+w /var/www/html/athletica/tmp \
  && mkdir -p /var/www/html/athletica_liveresultate/tmp \
  && chmod a+w /var/www/html/athletica_liveresultate/tmp
