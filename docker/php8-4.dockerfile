FROM php:8.4.0alpha2-apache

RUN echo "ServerName mbn-php8-4:80">>/etc/apache2/apache2.conf
RUN a2enmod rewrite
