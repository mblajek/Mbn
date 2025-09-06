FROM php:8.5-rc-apache

RUN echo "ServerName mbn-php8-5:80">>/etc/apache2/apache2.conf
RUN a2enmod rewrite
