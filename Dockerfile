FROM php:7.2-apache
RUN a2enmod headers
COPY ./ /var/www/html/
