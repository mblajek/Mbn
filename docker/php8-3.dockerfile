FROM php:8.3.0alpha2-apache

RUN echo "ServerName mbn-php8-3:80">>/etc/apache2/apache2.conf
RUN a2enmod rewrite
