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
RUN rm -r /app; ln -s /src /app

# Config files
RUN sed -i "s/AllowOverride None/AllowOverride All/g" /etc/apache2/apache2.conf
RUN a2enmod rewrite
ADD wp-config.php /src/wp-config.php
ADD .htaccess /src/.htaccess
ADD scripts /scripts
RUN chmod +x /scripts/*.sh

# Expose environment variables
ENV DB_HOST **LinkMe**
ENV DB_PORT **LinkMe**
ENV DB_NAME wordpress
ENV DB_USER admin
ENV DB_PASS **ChangeMe**
ENV URL "http://localhost:8080"
ENV DB_DUMP_URL "http://getproudcity.com/db.sql.gz"
ENV PROUD_URL "http://api.getproudcity.com/rest/v1.1"
ENV PROUD_PUBLIC ""
ENV PROUD_SECRET ""

EXPOSE 80
#VOLUME ["/app/wp-content"]
CMD ["/scripts/docker-run.sh"]