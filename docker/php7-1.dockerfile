FROM php:7.1-apache

RUN echo "ServerName mbn-php7-1:80">>/etc/apache2/apache2.conf
RUN a2enmod rewrite
