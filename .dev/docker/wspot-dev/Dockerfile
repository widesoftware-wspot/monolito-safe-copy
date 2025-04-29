FROM ubuntu:focal

LABEL maintainer="Wide Software"

WORKDIR /sites/wspot.com.br

# Set timezone and env vars
ENV TZ=America/Sao_Paulo \
    DEBIAN_FRONTEND=noninteractive \
    NVM_DIR=/root/.nvm \
    NODE_VERSION=v14.18.0

# Atualização base
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone \
    && apt-get update \
    && apt-get install -y curl wget vim gnupg2 ca-certificates lsb-release software-properties-common \
    && apt-get install -y nginx npm \
    && mkdir -p /var/log/nginx/ /var/log/php-fpm/ /run/php \
    && chmod -R 777 /var/log/ && chown -R www-data /sites/wspot.com.br/

# Configura NGINX
COPY nginx/nginx.conf /etc/nginx/nginx.conf
COPY nginx/conf.sites/* /etc/nginx/conf.sites/
COPY nginx/sites-enabled/* /etc/nginx/sites-enabled/

# Repositório PHP Ondrej
RUN LC_ALL=C.UTF-8 add-apt-repository ppa:ondrej/php -y && apt-get update

# Instala PHP 5.6 + módulos
RUN apt-get install -y php-pear php5.6 php5.6-bcmath php5.6-bz2 php5.6-cgi php5.6-cli php5.6-common \
    php5.6-curl php5.6-dba php5.6-dev php5.6-enchant php5.6-fpm php5.6-gd php5.6-gmp php5.6-imap \
    php5.6-interbase php5.6-intl php5.6-json php5.6-ldap php5.6-mbstring php5.6-mcrypt php5.6-mysql \
    php5.6-odbc php5.6-opcache php5.6-pgsql php5.6-phpdbg php5.6-pspell php5.6-readline php5.6-recode \
    php5.6-snmp php5.6-soap php5.6-sqlite3 php5.6-sybase php5.6-tidy php5.6-xml php5.6-xmlrpc php5.6-xsl \
    php5.6-zip php-xml php-mongodb php-mongo php-apcu

# Configuração PHP-FPM
RUN rm -rf /etc/php/5.6/fpm/pool.d/*
COPY php-fpm/wspot.com.br.conf /etc/php/5.6/fpm/pool.d/
COPY php-fpm/php-fpm.conf /etc/php/5.6/fpm/php-fpm.conf
COPY php-fpm/php.ini /etc/php/5.6/fpm/php.ini
COPY php-fpm/confs_customizadas.ini /etc/php/5.6/fpm/conf.d/

RUN touch /var/log/php5.6-fpm.log /var/log/php-fpm/slowlog-wspot.com.br.log \
    && chown www-data: /var/log/php5.6-fpm.log /var/log/php-fpm/slowlog-wspot.com.br.log

# Atualiza pacote do mongodb
RUN update-alternatives --set php /usr/bin/php5.6 \
    && update-alternatives --set phar /usr/bin/phar5.6 \
    && update-alternatives --set phar.phar /usr/bin/phar.phar5.6 \
    && update-alternatives --set phpize /usr/bin/phpize5.6 \
    && update-alternatives --set php-config /usr/bin/php-config5.6 \
    && pecl channel-update pecl.php.net \
    && pecl uninstall -r mongodb || true \
    && pecl install mongodb-1.2.0 \
    && echo "extension=/usr/lib/php/20131226/mongodb.so" >> /etc/php/5.6/cli/conf.d/30-mongodb.ini

# Instala NVM e Node.js
RUN curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.38.0/install.sh | bash \
    && . $NVM_DIR/nvm.sh \
    && nvm install $NODE_VERSION \
    && nvm alias default $NODE_VERSION \
    && nvm use default

ENV NODE_PATH=$NVM_DIR/versions/node/$NODE_VERSION/lib/node_modules
ENV PATH=$NVM_DIR/versions/node/$NODE_VERSION/bin:$PATH
