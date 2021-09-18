FROM php:7.0-apache

RUN echo "ServerName mbn-php7-0:80">>/etc/apache2/apache2.conf
RUN a2enmod rewrite
