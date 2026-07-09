#!/bin/bash
# ---------------------------------------------------------------------
# Copyright (C) 2026 DevPanel
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as
# published by the Free Software Foundation version 3 of the
# License.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Affero General Public License for more details.
#
# For GNU Affero General Public License see <https://www.gnu.org/licenses/>.
# ----------------------------------------------------------------------

export PATH="$APP_ROOT/vendor/bin:$PATH"
cd $APP_ROOT

# Install regardless of security audit.
export COMPOSER_NO_AUDIT=1
export COMPOSER_NO_BLOCKING=1
# Keep deprecated var for compatibility with Composer versions where
# COMPOSER_NO_BLOCKING/--no-blocking is not supported yet.
export COMPOSER_NO_SECURITY_BLOCKING=1

#== Remove root-owned files.
echo
echo Remove root-owned files.
time sudo rm -rf lost+found

#== Composer install.
echo
if [ -f composer.json ]; then
  if composer show cweagans/composer-patches ^2 &> /dev/null; then
    echo 'Update patches.lock.json.'
    composer prl
    echo
  fi
else
  echo 'Generate composer.json.'
  source .devpanel/composer_setup.sh
  echo
fi
composer -n install --no-progress

#== Create the public files directory.
if [ ! -d web/sites/default/files ]; then
  echo
  echo 'Create the public files directory.'
  mkdir -pm 775 web/sites/default/files
else
  sudo chmod 775 -R web/sites/default/files
fi

#== Create the private files directory.
if [ ! -d private ]; then
  echo
  echo 'Create the private files directory.'
  mkdir -m 775 private
else
  sudo chmod 775 -R private
fi

#== Create the config sync directory.
if [ ! -d config/sync ]; then
  echo
  echo 'Create the config sync directory.'
  mkdir -pm 775 config/sync
else
  sudo chmod 775 -R config
fi

if [ -z "$(drush status --field=db-status)" ]; then
  #== Extract static files.
  if [ -f .devpanel/dumps/files.tgz ]; then
    echo  'Extract static files.'
    sudo tar xzf .devpanel/dumps/files.tgz -C web/sites/default/files
    sudo rm -rf .devpanel/dumps/files.tgz
  fi

  #== Import database.
  if [[ -f .devpanel/dumps/db.sql.gz ]]; then
    echo 'Import database.'
    sudo chmod u+w .devpanel/dumps/db.sql.gz
    drush sqlq --file-delete --file=../.devpanel/dumps/db.sql.gz
    echo 'Update database.'
    drush -n updb
  fi
fi

php web/modules/contrib/automatic_updates/auto-update

#== Warm up caches.
echo
echo 'Run cron.'
drush cron
echo
echo 'Populate caches.'
drush cache:warm &> /dev/null || :
.devpanel/warm
.devpanel/warm /user/login

#== Fix ownership for strict permissions.
echo
echo 'Fix ownership for strict permissions.'
sudo chown -R ${APACHE_RUN_USER:=www-data} web/sites/default/files private config/sync
