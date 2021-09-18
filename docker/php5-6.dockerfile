FROM php:5.6-apache

RUN echo "ServerName mbn-php5-6:80">>/etc/apache2/apache2.conf
RUN a2enmod rewrite
