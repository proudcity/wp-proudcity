FROM tutum/apache-php:latest
MAINTAINER Jeff Lyon <jeff@proudcity.com>, Alex Schmoe <alex@proudcity.com>

# Dependencies
RUN apt-get update && \
    DEBIAN_FRONTEND=noninteractive apt-get -yq install \
    mysql-client \
    php5-cli \
    git

WORKDIR /

# Install composer
RUN curl -sS https://getcomposer.org/installer | php
RUN mv composer.phar /usr/local/bin/composer

# Install wp-cli
RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
RUN php wp-cli.phar --info --allow-root
RUN chmod +x wp-cli.phar
RUN mv wp-cli.phar /usr/local/bin/wp

# Build Wordpress with Compser
ADD composer.json .
RUN composer install
RUN rm -r /app; ln -s /wordpress /app

# Config files
RUN sed -i "s/AllowOverride None/AllowOverride All/g" /etc/apache2/apache2.conf
RUN a2enmod rewrite
ADD wp-config.php /wordpress/wp-config.php
ADD .htaccess /wordpress/.htaccess
ADD bin /bin
RUN chmod +x /bin/*.sh

EXPOSE 80
#VOLUME ["/app/wp-content"]
CMD ["/bin/docker-run.sh"]