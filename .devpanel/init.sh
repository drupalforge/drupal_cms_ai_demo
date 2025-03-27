#!/usr/bin/env bash
set -eu -o pipefail
cd $APP_ROOT

LOG_FILE="logs/init-$(date +%F-%T).log"
exec > >(tee $LOG_FILE) 2>&1

TIMEFORMAT=%lR
# For faster performance, don't audit dependencies automatically.
export COMPOSER_NO_AUDIT=1
# For faster performance, don't install dev dependencies.
export COMPOSER_NO_DEV=1

#== Remove root-owned files.
echo
echo Remove root-owned files.
time sudo rm -rf lost+found

#== Composer install.
if [ ! -f composer.json ]; then
  echo
  echo 'Generate composer.json.'
  time source .devpanel/composer_setup.sh
fi
echo
time composer -n update --no-dev --no-progress

#== Create the private files directory.
if [ ! -d private ]; then
  echo
  echo 'Create the private files directory.'
  time mkdir private
fi

#== Generate hash salt.
if [ ! -f .devpanel/salt.txt ]; then
  echo
  echo 'Generate hash salt.'
  time openssl rand -hex 32 > .devpanel/salt.txt
fi

#== Install Drupal.
if [ -z "$(mysql -h $DB_HOST -P $DB_PORT -u $DB_USER -p$DB_PASSWORD $DB_NAME -e 'show tables')" ]; then
  time drush -n si

  #== Apply the AI recipe.
  if [ -n "${DP_AI_VIRTUAL_KEY:-}" ]; then
    drush -q recipe ../recipes/drupal_cms_ai \
      --input=drupal_cms_ai.provider=openai \
      --input=drupal_cms_ai.openai_api_key=$DP_AI_VIRTUAL_KEY
    drush config:set -n ai_provider_openai.settings host "https://ai.drupalforge.org"
    drush config:set -n ai_provider_openai.settings moderation false --input-format yaml
    drush config:set -n ai.settings default_providers.chat.provider_id openai
    drush config:set -n ai.settings default_providers.chat.model_id gpt-4
    drush -n key-save openai_api_key --key-provider=env --key-provider-settings='{
      "env_variable": "DP_AI_VIRTUAL_KEY",
      "base64_encoded": false,
      "strip_line_breaks": true
    }'
  fi

  echo
  echo 'Tell Automatic Updates about patches.'
  time drush -n cset --input-format=yaml package_manager.settings additional_known_files_in_project_root '["patches.json", "patches.lock.json"]'
else
  drush -n updb
fi

#== Finish measuring script time.
INIT_DURATION=$SECONDS
INIT_HOURS=$(($INIT_DURATION / 3600))
INIT_MINUTES=$(($INIT_DURATION % 3600 / 60))
INIT_SECONDS=$(($INIT_DURATION % 60))
printf "\nTotal elapsed time: %d:%02d:%02d\n" $INIT_HOURS $INIT_MINUTES $INIT_SECONDS
