FROM php:7.3-apache

RUN echo "ServerName mbn-php7-3:80">>/etc/apache2/apache2.conf
RUN a2enmod rewrite
