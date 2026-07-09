#!/bin/bash
# ---------------------------------------------------------------------
# Copyright (C) 2024 DevPanel
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
echo -e "-------------------------------"
echo -e "| DevPanel Quickstart Creator |"
echo -e "-------------------------------\n"

# Prepare.
export PATH="$APP_ROOT/vendor/bin:$PATH"
DUMPS_DIR=/tmp/devpanel/quickstart/dumps
mkdir -p $DUMPS_DIR
mkdir -p .devpanel/dumps
drush cr

# Step 1 - Export database.
cd $APP_ROOT
echo -e "> Export database to $APP_ROOT/.devpanel/dumps"
drush sql-dump --result-file=../.devpanel/dumps/db.sql --gzip

# Step 2 - Export static files.
echo -e "> Compress static files"
tar czf $DUMPS_DIR/files.tgz -C $WEB_ROOT/sites/default/files .
echo -e "> Store files.tgz to $APP_ROOT/.devpanel/dumps"
mv $DUMPS_DIR/files.tgz .devpanel/dumps/files.tgz
