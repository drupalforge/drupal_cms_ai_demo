Files in the .devpanel directory control DevPanel deployment for this app.


## Startup scripts

- __[custom_package_installer.sh](custom_package_installer.sh):__ Installs
  extra system software.
- __[init-container.sh](init-container.sh):__ Checks for a database dump and
  imports it.
- __[init.sh](init.sh):__ Performs additional startup tasks. Supporting files:
  - __[composer_setup.sh](composer_setup.sh):__ Generates composer.json and
    composer.lock.
  - __[settings.devpanel.php](settings.devpanel.php):__ Settings for running
    Drupal as a DevPanel app.
  - __[drupal-settings.patch](drupal-settings.patch):__ Patch for settings.php
    to include settings.devpanel.php.


## Git integration

- __[config.yml](config.yml):__ Defines tasks to run when Git is configured to
  update the app automatically.


## Deployment

- __[re-config.sh](re-config.sh):__ Runs when container configuration is
  changed in DevPanel or the app is deployed to a hosting provider.


## Creating a Docker image

- __[create_quickstart.sh](quickstart/create_quickstart.sh):__ Archives the
  database and files for the Drupal Forge Docker Publish Workflow which can be
  added in GitHub Actions.
