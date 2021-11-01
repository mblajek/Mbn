FROM php:8.0-apache

RUN echo "ServerName mbn-php8-0:80">>/etc/apache2/apache2.conf
RUN a2enmod rewrite

RUN apt-get update
RUN apt-get install -y libzip-dev
RUN docker-php-ext-install zip
