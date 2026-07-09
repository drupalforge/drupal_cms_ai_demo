Files in the `.devpanel` directory control DevPanel deployment for this app.


## Startup scripts

- [`custom_package_installer.sh`](custom_package_installer.sh): Installs
  extra system software. Runs as root. This is called by
  /scripts/apache-start.sh before Apache starts.
- [`init-container.sh`](init-container.sh): Startup for registry mode (a
  DevPanel template setting). Imports a database dump and copies files from the
  image’s app directory to an external volume.
- [`init.sh`](init.sh): Startup for non-registry mode and image build.
  Supporting files:
  - [`composer_setup.sh`](composer_setup.sh): Generates composer.json and
    composer.lock files. Not needed if you supply these files yourself.
  - [`settings.devpanel.php`](settings.devpanel.php): Settings for running
    Drupal as a DevPanel app.
  - [`drupal-settings.patch`](drupal-settings.patch): Patch for settings.php
    to include settings.devpanel.php. Installed by the post-drupal-scaffold-cmd
    script. Make sure this works with your Composer project.
  - [`warm`](warm): Loads any path to build caches. If no path is provided,
    defaults to /.
- [`Dockerfile`](Dockerfile): Provides the `COMPOSER_HOME` variable required by
  the Drupal Automatic Updates web UI.


## Git integration

- [`config.yml`](config.yml): Defines tasks to run when Git is configured to
  update the app automatically.


## Deployment

- [`re-config.sh`](re-config.sh): Runs when container configuration is
  changed in DevPanel or the app is deployed to a hosting provider.


## Creating a Docker image

- [`create_quickstart.sh`](create_quickstart.sh): Archives the database and
  files for the _Drupal Forge Docker Publish Workflow_ which can be added in
  [GitHub Actions](../../actions).
