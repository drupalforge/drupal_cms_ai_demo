#!/usr/bin/env bash
if [ -n "${DEBUG_SCRIPT:-}" ]; then
  set -x
fi
set -eu -o pipefail
cd $APP_ROOT

LOG_FILE="logs/init-$(date +%F-%T).log"
exec > >(tee $LOG_FILE) 2>&1

TIMEFORMAT=%lR
# For faster performance, don't audit dependencies automatically.
export COMPOSER_NO_AUDIT=1
# For faster performance, don't install dev dependencies.
export COMPOSER_NO_DEV=1

# Install VSCode Extensions
if [ -n "${DP_VSCODE_EXTENSIONS:-}" ]; then
  IFS=','
  for value in $DP_VSCODE_EXTENSIONS; do
    time code-server --install-extension $value
  done
fi

#== Remove root-owned files.
echo
echo Remove root-owned files.
time sudo rm -rf lost+found

#== Composer install.
echo
if [ -f composer.json ]; then
  echo 'Run composer update.'
  time composer -n update --no-dev --no-progress
  echo
  # Update patches.lock.json if composer-patches v2 is installed
  if composer show --locked cweagans/composer-patches ^2 &> /dev/null; then
    echo 'Update patches.lock.json.'
    time composer prl || echo "Note: patches-relock command not available, skipping."
    echo
  fi
else
  echo 'Generate composer.json.'
  time source .devpanel/composer_setup.sh
  time source .devpanel/composer_extra.sh
  echo
  time composer -n update --no-dev --no-progress
fi

#== Create the private files directory.
if [ ! -d private ]; then
  echo
  echo 'Create the private files directory.'
  time mkdir private
fi

#== Create the config sync directory.
if [ ! -d config/sync ]; then
  echo
  echo 'Create the config sync directory.'
  time mkdir -p config/sync
fi

#== Generate hash salt.
if [ ! -f .devpanel/salt.txt ]; then
  echo
  echo 'Generate hash salt.'
  time openssl rand -hex 32 > .devpanel/salt.txt
fi

#== Install Drupal.
echo
if [ -z "$(drush status --field=db-status)" ]; then
  echo 'Install Drupal.'
  time drush -n si

  #== Apply the AI recipe.
  if [ -n "${DP_AI_VIRTUAL_KEY:-}" ]; then
    echo
    time drush -n en ai_provider_litellm
    drush -n key-save litellm_api_key --label="LiteLLM API key" --key-provider=env --key-provider-settings='{
      "env_variable": "DP_AI_VIRTUAL_KEY",
      "base64_encoded": false,
      "strip_line_breaks": true
    }'
    drush -n cset ai_provider_litellm.settings api_key litellm_api_key
    drush -n cset ai_provider_litellm.settings moderation false --input-format yaml
    drush -n cset ai_provider_litellm.settings host "${DP_AI_HOST:="https://ai.drupalforge.org"}"
    drush -q recipe ../recipes/drupal_cms_ai --input=drupal_cms_ai.provider=litellm
    drush -n cset ai.settings default_providers.chat.provider_id litellm
    drush -n cset ai.settings default_providers.chat.model_id openai/gpt-4o-mini
    drush -n cset ai.settings default_providers.chat_with_complex_json.provider_id litellm
    drush -n cset ai.settings default_providers.chat_with_complex_json.model_id openai/gpt-4o-mini
    drush -n cset ai.settings default_providers.chat_with_image_vision.provider_id litellm
    drush -n cset ai.settings default_providers.chat_with_image_vision.model_id openai/gpt-4o-mini
    drush -n cset ai.settings default_providers.chat_with_structured_response.provider_id litellm
    drush -n cset ai.settings default_providers.chat_with_structured_response.model_id openai/gpt-4o-mini
    drush -n cset ai.settings default_providers.chat_with_tools.provider_id litellm
    drush -n cset ai.settings default_providers.chat_with_tools.model_id openai/gpt-4o-mini
    drush -n cset ai.settings default_providers.embeddings.provider_id litellm
    drush -n cset ai.settings default_providers.embeddings.model_id openai/text-embedding-3-small
    drush -n cset ai.settings default_providers.text_to_speech.provider_id litellm
    drush -n cset ai.settings default_providers.text_to_speech.model_id openai/gpt-4o-mini-realtime-preview
    drush -n cset ai_assistant_api.ai_assistant.drupal_cms_assistant llm_provider __default__
    drush -n cset klaro.klaro_app.deepchat status 0
  fi

  echo
  echo 'Tell Automatic Updates about patches.'
  drush -n cset --input-format=yaml package_manager.settings additional_trusted_composer_plugins '["cweagans/composer-patches"]'
  drush -n cset --input-format=yaml package_manager.settings additional_known_files_in_project_root '["patches.json", "patches.lock.json"]'
  time drush ev '\Drupal::moduleHandler()->invoke("automatic_updates", "modules_installed", [[], FALSE])'
else
  echo 'Update database.'
  time drush -n updb
fi

#== Install Tool module and submodules.
echo
echo 'Install Tool modules.'
drush -y pm:en tool tool_ai_connector tool_content tool_content_translation tool_entity tool_system tool_explorer tool_user

#== Install AI provider modules.
echo
echo 'Install AI provider modules.'
drush -y pm:en ai_provider_openai

#== Install AI explorer and logging tools.
echo
echo 'Install AI explorer and logging tools.'
drush -y pm:en ai_agents_explorer ai_api_explorer ai_logging ai_observability

#== Install core Flowdrop modules.
echo
echo 'Install core Flowdrop modules.'
drush -y pm:en flowdrop flowdrop_ui flowdrop_runtime flowdrop_pipeline flowdrop_workflow

#== Install Flowdrop UI Agents.
echo
echo 'Install Flowdrop UI Agents.'
drush -y pm:en flowdrop_ui_agents

#== Enable AI logging (requests and responses).
drush -n cset ai_logging.settings prompt_logging 1
drush -n cset ai_logging.settings prompt_logging_output 1

#== Apply Bundle Lister Demo recipe.
echo
echo 'Apply Bundle Lister Demo recipe.'
time drush -q recipe ../recipes/bundle_lister_demo

#== Apply Bundle Lister Demo recipe.
echo
echo 'Apply Alt Text Evaluator Demo recipe.'
time drush -q recipe ../recipes/alt_text_evaluator_demo

#== Disable Klaro consent for DeepChat chatbot.
drush -n cset klaro.klaro_app.deepchat status 0

#== Warm up caches.
echo
echo 'Run cron.'
time drush cron
echo
echo 'Populate caches.'
time drush cache:warm
time .devpanel/warm

#== Finish measuring script time.
INIT_DURATION=$SECONDS
INIT_HOURS=$(($INIT_DURATION / 3600))
INIT_MINUTES=$(($INIT_DURATION % 3600 / 60))
INIT_SECONDS=$(($INIT_DURATION % 60))
printf "\nTotal elapsed time: %d:%02d:%02d\n" $INIT_HOURS $INIT_MINUTES $INIT_SECONDS
