FROM centos
MAINTAINER lizhenliang1@360.cn

ENV NGINX_V=1.15.6
ENV PHP_V=5.6.36

# Install Dependencies
RUN yum install epel-release -y && \
    yum install -y gcc gcc-c++ make gd-devel libxml2-devel \
    libcurl-devel libjpeg-devel libpng-devel openssl-devel \
    libmcrypt-devel libxslt-devel libtidy-devel autoconf \
    iproute net-tools telnet wget curl && \
    yum clean all && \
    rm -rf /var/cache/yum/* && \
    wget https://www.openssl.org/source/openssl-1.0.2o.tar.gz && \
    tar zxf openssl-1.0.2o.tar.gz && \
    cd openssl-1.0.2o && \
    ./config --prefix=/usr/local/openssl && \
    make -j 4 && make install && \
    cd / && \
    wget https://curl.haxx.se/download/curl-7.60.0.tar.gz && \
    tar zxf curl-7.60.0.tar.gz && \
    cd curl-7.60.0 && \
    ./configure --prefix=/usr/local/curl && \
    make -j 4 && make install && \
    cd / && rm -rf /usr/lib/python* openssl* curl*

# Install PHP
RUN wget http://docs.php.net/distributions/php-${PHP_V}.tar.gz && \
    tar zxf php-${PHP_V}.tar.gz && \
    cd php-${PHP_V} && \
    ./configure --prefix=/usr/local/php \
    --with-config-file-path=/usr/local/php/etc \
    --with-config-file-scan-dir=/usr/local/php/etc/php.d \
    --enable-fpm --enable-opcache --enable-static=no \
    --with-mysql --with-mysqli --with-pdo-mysql \
    --enable-phar --with-pear --enable-session \
    --enable-sysvshm --with-tidy --with-openssl=/usr/local/openssl \
    --with-zlib --with-curl=/usr/local/curl --with-gd --enable-bcmath \
    --with-jpeg-dir --with-png-dir --with-freetype-dir \
    --with-iconv --enable-mbstring --with-mhash \
    --with-mcrypt --enable-hash --with-xsl && \
    make -j 4 && make install && \
    cp php.ini-production /usr/local/php/etc/php.ini && \
    cp sapi/fpm/php-fpm.conf /usr/local/php/etc/php-fpm.conf && \
    cp sapi/fpm/init.d.php-fpm /etc/init.d/php-fpm && \
    chmod +x /etc/init.d/php-fpm && \
    cd / && \
    wget http://pecl.php.net/get/redis-3.1.6.tgz && \
    tar zxf redis-3.1.6.tgz && \
    cd redis-3.1.6 && \
    /usr/local/php/bin/phpize && \
    ./configure --with-php-config=/usr/local/php/bin/php-config && \
    make -j 4 && make install && \
    sed -i '873a\extension="redis.so"' /usr/local/php/etc/php.ini && \
    cd / && rm -rf php* redis*
COPY php.ini /usr/local/php/etc/php.ini

# Install Nginx
RUN wget http://nginx.org/download/nginx-${NGINX_V}.tar.gz && \
    tar zxf nginx-${NGINX_V}.tar.gz && \
    cd nginx-${NGINX_V} && \
    ./configure --prefix=/usr/local/nginx \
    --pid-path=/var/run/nginx.pid \
    --error-log-path=/data/nginx/logs/error.log \
    --http-log-path=/data/nginx/logs/access.log \
    --http-client-body-temp-path=/data/nginx/client_body_temp \
    --http-proxy-temp-path=/data/nginx/proxy_temp \
    --http-fastcgi-temp-path=/data/nginx/fastcgi_temp \
    --http-uwsgi-temp-path=/data/nginx/uwsgi_temp \
    --http-scgi-temp-path=/data/nginx/scgi_temp \
    --with-http_ssl_module \
    --with-http_v2_module \
    --with-http_realip_module \
    --with-http_gunzip_module \
    --with-http_gzip_static_module \
    --with-http_stub_status_module \
    --with-pcre \
    --with-stream \
    --with-stream_ssl_module && \
    make && make install && \
    rm -rf /usr/local/nginx/html/* && \
    echo "ok" >> /usr/local/nginx/html/status.html && \
    echo "<?php echo ok;?>" >> /usr/local/nginx/html/status.php && \
    cd /

COPY nginx.conf /usr/local/nginx/conf
ENV PATH $PATH:/usr/local/nginx/sbin

COPY docker-entrypoint.sh /usr/bin
ENTRYPOINT ["docker-entrypoint.sh"]

WORKDIR /usr/local

EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]
