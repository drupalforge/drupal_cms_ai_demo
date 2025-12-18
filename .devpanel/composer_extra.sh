#!/usr/bin/env bash
set -eu -o pipefail
cd $APP_ROOT

# Currently the recipe is beta.
composer config minimum-stability dev

# Get Flowdrop UI Agents
composer require 'drupal/flowdrop:1.x-dev@dev'
composer require 'drupal/flowdrop_ui_agents:1.0.x-dev@dev'

# We need to build flowdrop ui.
cd web/modules/contrib/flowdrop/modules/flowdrop_ui/app/flowdrop && npm install && npm run build && cd ../../../../../../..
