FROM php:7.2-apache

RUN echo "ServerName mbn-php7-2:80">>/etc/apache2/apache2.conf
RUN a2enmod rewrite
