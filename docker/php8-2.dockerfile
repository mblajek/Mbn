FROM php:8.2.0RC2-apache

RUN echo "ServerName mbn-php8-2:80">>/etc/apache2/apache2.conf
RUN a2enmod rewrite
