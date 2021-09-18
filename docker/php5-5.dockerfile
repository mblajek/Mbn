FROM php:5.5-apache

RUN echo "ServerName mbn-php5-5:80">>/etc/apache2/apache2.conf
RUN a2enmod rewrite
