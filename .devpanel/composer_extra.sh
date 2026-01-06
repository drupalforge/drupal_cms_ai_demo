#!/usr/bin/env bash
set -eu -o pipefail
cd $APP_ROOT

# Currently the recipe is beta.
composer config minimum-stability dev

# Get Flowdrop UI Agents
composer require 'drupal/flowdrop:1.x-dev@dev'
composer require 'drupal/flowdrop_ui_agents:1.0.x-dev@dev'
composer require 'drupal/ai_provider_openai:^1.2'
composer require 'drupal/tool:^1.0@alpha'

# Get the agent creation agent
composer require 'drupal/ai_agent_agent:1.x-dev@dev'
