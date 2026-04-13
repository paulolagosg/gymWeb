FROM ubuntu:24.04

LABEL maintainer="Taylor Otwell"

ARG WWWGROUP
ARG NODE_VERSION=22.14.0
ARG MYSQL_CLIENT="mysql-client"
ARG POSTGRES_VERSION=17
ARG INSTALL_EXTRA_TOOLS=false
ARG INSTALL_POSTGRES_SUPPORT=false
ARG INSTALL_EXTRA_PHP_EXTENSIONS=false

WORKDIR /var/www/html

ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=UTC
ENV SUPERVISOR_PHP_COMMAND="/usr/bin/php -d variables_order=EGPCS /var/www/html/artisan serve --host=0.0.0.0 --port=80"
ENV SUPERVISOR_PHP_USER="sail"

RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

RUN echo "Acquire::http::Pipeline-Depth 0;" > /etc/apt/apt.conf.d/99custom && \
    echo "Acquire::http::No-Cache true;" >> /etc/apt/apt.conf.d/99custom && \
    echo "Acquire::BrokenProxy    true;" >> /etc/apt/apt.conf.d/99custom

RUN apt-get update \
    && mkdir -p /etc/apt/keyrings \
    && apt-get install -y gnupg gosu curl ca-certificates zip unzip xz-utils git supervisor sqlite3 libcap2-bin \
    && if [ "$INSTALL_EXTRA_TOOLS" = "true" ]; then apt-get install -y python3 dnsutils librsvg2-bin fswatch ffmpeg nano; fi \
    && curl -sS 'https://keyserver.ubuntu.com/pks/lookup?op=get&search=0xb8dc7e53946656efbce4c1dd71daeaab4ad4cab6' | gpg --dearmor | tee /etc/apt/keyrings/ppa_ondrej_php.gpg > /dev/null \
    && echo "deb [signed-by=/etc/apt/keyrings/ppa_ondrej_php.gpg] https://ppa.launchpadcontent.net/ondrej/php/ubuntu noble main" > /etc/apt/sources.list.d/ppa_ondrej_php.list \
    && apt-get update \
    && apt-get install -y php8.4-cli \
    php8.4-sqlite3 php8.4-gd \
    php8.4-curl php8.4-mysql php8.4-mbstring \
    php8.4-xml php8.4-zip php8.4-bcmath php8.4-soap \
    php8.4-intl php8.4-readline \
    php8.4-redis php8.4-xdebug \
    && if [ "$INSTALL_POSTGRES_SUPPORT" = "true" ]; then \
    curl -sS https://www.postgresql.org/media/keys/ACCC4CF8.asc | gpg --dearmor | tee /etc/apt/keyrings/pgdg.gpg >/dev/null \
    && echo "deb [signed-by=/etc/apt/keyrings/pgdg.gpg] http://apt.postgresql.org/pub/repos/apt noble-pgdg main" > /etc/apt/sources.list.d/pgdg.list \
    && apt-get update \
    && apt-get install -y php8.4-pgsql postgresql-client-$POSTGRES_VERSION; \
    fi \
    && if [ "$INSTALL_EXTRA_PHP_EXTENSIONS" = "true" ]; then apt-get install -y php8.4-mongodb php8.4-imap php8.4-ldap php8.4-msgpack php8.4-igbinary php8.4-swoole php8.4-memcached php8.4-pcov php8.4-imagick; fi \
    && curl -sLS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer \
    && case "$(dpkg --print-architecture)" in \
    amd64) node_arch='x64' ;; \
    arm64) node_arch='arm64' ;; \
    *) echo "Arquitectura no soportada para Node.js: $(dpkg --print-architecture)" && exit 1 ;; \
    esac \
    && curl -fsSL "https://nodejs.org/dist/v${NODE_VERSION}/node-v${NODE_VERSION}-linux-${node_arch}.tar.xz" -o /tmp/node.tar.xz \
    && tar -xJf /tmp/node.tar.xz -C /usr/local --strip-components=1 \
    && rm /tmp/node.tar.xz \
    && apt-get install -y $MYSQL_CLIENT \
    && apt-get -y autoremove \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN setcap "cap_net_bind_service=+ep" /usr/bin/php8.4

RUN userdel -r ubuntu
RUN groupadd --force -g $WWWGROUP sail
RUN useradd -ms /bin/bash --no-user-group -g $WWWGROUP -u 1337 sail

COPY start-container /usr/local/bin/start-container
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY php.ini /etc/php/8.4/cli/conf.d/99-sail.ini
RUN chmod +x /usr/local/bin/start-container

EXPOSE 80/tcp

ENTRYPOINT ["start-container"]