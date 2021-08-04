# syntax=docker/dockerfile:1
FROM php:8.0-cli

# метатеги
LABEL description="Тестовое задание Leads.Tech" \
    maintainer="develop@dicr.org"

# добавляем расширения php
RUN apt update && \
    docker-php-ext-install pcntl sysvmsg

# php.ini
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# копируем файлы проекта
WORKDIR /usr/src/myapp
COPY . .

# запуск
CMD [ "php", "./run" ]
