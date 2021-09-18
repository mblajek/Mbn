FROM php:7.4-apache

RUN echo "ServerName mbn-php7-4:80">>/etc/apache2/apache2.conf
RUN a2enmod rewrite
