FROM php:8.1-rc-apache

RUN echo "ServerName mbn-php8-1:80">>/etc/apache2/apache2.conf
RUN a2enmod rewrite
