# Drupal CMS + AI Demo with Flowdrop UI

This is a demo Drupal CMS project that integrates AI capabilities using custom AI modules and Flowdrop UI for building AI-powered applications. The project showcases how to leverage AI models within Drupal to enhance content management and user interactions.

## How to setup

Either use DrupalForge to run it in the cloud, or follow the instructions below to set it up locally.

### Prerequisites
* DDEV
* Git to clone the repository

### Steps to run locally
1. Clone the repository: `git clone git@github.com:drupalforge/drupal_cms_ai_demo_flowdrop.git`
2. `cd drupal_cms_ai_demo_flowdrop`
3. `ddev start`

### Access the site
Once the setup is complete, you can access the Drupal site at `http://drupal-cms-ai-demo-flowdrop.ddev.site` and log in with username `admin` and password `admin`.

## Develop Flowdrop Agents Applications
1. Run `ddev setup-flowdrop-dev` to set up the Flowdrop development environment.
2. Your actual modules will be in the `modules/` directory, but symlinked into `web/modules/contrib/` for Drupal to find them.
3. DO NOT add this module to the repo. Keep it local only for development purposes.
