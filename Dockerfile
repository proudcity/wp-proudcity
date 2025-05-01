FROM php:8.2-apache-bookworm

# Add Github private repo key
ARG SSH_KEY
RUN mkdir -p /root/.ssh \
  && echo "${SSH_KEY}" >> /root/.ssh/id_rsa \
  && chmod 400 /root/.ssh/id_rsa
COPY etc/known_hosts.github /root/.ssh/known_hosts
RUN ls /root/.ssh && cat /root/.ssh/id_rsa

# setup cgroupv2
RUN mkdir -p /etc/sysctl.d/
COPY etc/99-cgroup.conf /etc/sysctl.d/99-cgroup.conf
RUN cat /etc/sysctl.d/99-cgroup.conf

# install the PHP extensions we need
RUN apt-get update \
  && apt-get -y upgrade \
  && apt-get install -y --no-install-recommends vim libpng-dev libjpeg-dev mariadb-client unzip openssh-client git libcurl4-openssl-dev libmcrypt-dev \
  && rm -rf /var/lib/apt/lists/* \
  && docker-php-ext-configure gd --with-jpeg \
  && docker-php-ext-install gd mysqli opcache bcmath \
  && a2enmod rewrite expires

RUN pecl install mcrypt-1.0.6

# install phpredis extension
# From http://stackoverflow.com/questions/31369867/how-to-install-php-redis-extension-using-the-official-php-docker-image-approach
RUN pecl install -o -f redis \
  &&  rm -rf /tmp/pear \
  &&  echo "extension=redis.so" > /usr/local/etc/php/conf.d/redis.ini

# set recommended PHP.ini settings
# see https://secure.php.net/manual/en/opcache.installation.php
RUN { \
  echo 'opcache.memory_consumption=128'; \
  echo 'opcache.interned_strings_buffer=8'; \
  echo 'opcache.max_accelerated_files=4000'; \
  echo 'opcache.revalidate_freq=60'; \
  echo 'opcache.fast_shutdown=1'; \
  echo 'opcache.enable_cli=1'; \
  } > /usr/local/etc/php/conf.d/opcache-recommended.ini

COPY etc/apache-vhost.conf /etc/apache2/sites-enabled/000-default.conf
COPY etc/php.ini /usr/local/etc/php/php.ini

RUN echo "ServerTokens Prod\nServerSignature Off" >> /etc/apache2/conf-available/harden.conf && \
  a2enconf harden

RUN mkdir -p /app
COPY composer.json /app/
WORKDIR /app

# Install composer.json file
RUN curl -k -o /tmp/composer.phar https://getcomposer.org/download/2.8.3/composer.phar \
  && mv /tmp/composer.phar /usr/local/bin/composer && chmod a+x /usr/local/bin/composer
RUN php -dmemory_limit=128M /usr/local/bin/composer install

# Explode out the gravityforms plugins in modules/*
#RUN   cp -r /app/wordpress/wp-content/plugins/gravityforms/modules/* /app/wordpress/wp-content/plugins && rm -r /app/wordpress/wp-content/plugins/gravityforms/modules

#RUN curl -o /tmp/markdown.zip https://littoral.michelf.ca/code/php-markdown/php-markdown-extra-1.2.8.zip \
#  	&& unzip /tmp/ markdown.zip -d  /app/wordpress/wp-content/plugins \
#  	&& mv  /app/wordpress/wp-content/plugins/PHP\ Markdown\ Extra\ 1.2.8/markdown.php  /app/wordpress/wp-content/plugins/ \
#  	&& rm -rf  /app/wordpress/wp-content/plugins/PHP\ Markdown\ Extra\ 1.2.8/ \
# && rm -rf /tmp/markdown.zip

RUN curl -o /tmp/wp-cli.phar https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
RUN cd /tmp && chmod +x wp-cli.phar \
  && mv wp-cli.phar /usr/local/bin/wp \
  && php -dmemory_limit=128M /usr/local/bin/wp package install wp-media/wp-rocket-cli:trunk --allow-root

# COPY etc/.htaccess_extra .htaccess_extra
# RUN cat .htaccess_extra >> .htaccess && rm .htaccess_extra && cat .htaccess
# RUN cat /entrypoint.sh

WORKDIR /app/wordpress

#### --- Configure entrypoint ---
COPY bin/entrypoint.sh /entrypoint.sh
COPY bin /app/bin/
COPY updates /app/updates/
COPY www/ /app/wordpress

RUN mkdir -p .well-known
COPY /etc/security.txt /app/wordpress/.well-known/security.txt

RUN chmod -R +x /app/bin
RUN chmod 755 /app/wordpress/wp-content

RUN mkdir /app/wordpress/wp-content/uploads
# RUN mkdir /app/wordpress/wp-content/cache

RUN chmod 777 /app/wordpress/wp-content/uploads
RUN chmod 777 /app/wordpress/wp-content/cache

RUN chmod -R a+rX /app/wordpress
RUN chown -R www-data:www-data /app/wordpress

# VOLUME /app/wordpress/wp-content/cache
EXPOSE 80

# grr, ENTRYPOINT resets CMD now
ENTRYPOINT ["/entrypoint.sh"]
CMD ["apache2-foreground"]
#CMD apache2 -D FOREGROUND
