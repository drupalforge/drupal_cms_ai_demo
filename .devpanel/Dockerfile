ENV COMPOSER_HOME="${HOME}/.config/composer"

USER root

# Install the PHP PostgreSQL extension.
RUN docker-php-ext-install pgsql

USER ${USER}
