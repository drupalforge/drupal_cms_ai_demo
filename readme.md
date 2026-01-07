# Drupal CMS + AI Demo with Flowdrop UI

This is a demo Drupal CMS project that integrates AI capabilities using custom AI modules and Flowdrop UI for building AI-powered applications. The project showcases how to leverage AI models within Drupal to enhance content management and user interactions.

## How to setup

Either use DrupalForge to run it in the cloud, or follow the instructions below to set it up locally.

### Prerequisites
* DDEV
* Git to clone the repository
* OpenAI API key (for AI features)

### Set up your OpenAI API key

Add your OpenAI API key to DDEV's global config so it's available to all projects:

```bash
# Edit DDEV global config
ddev config global --web-environment-add="OPENAI_API_KEY=sk-your-key-here"
```

Or add it to `~/.ddev/global_config.yaml`:

```yaml
web_environment:
  - OPENAI_API_KEY=sk-your-key-here
```

Get your API key from: https://platform.openai.com/settings/organization/api-keys

### Steps to run locally
1. Clone the repository: `git clone git@github.com:drupalforge/drupal_cms_ai_demo_flowdrop.git`
2. `cd drupal_cms_ai_demo_flowdrop`
3. `ddev start`

The AI recipe will be automatically applied using your OpenAI API key.

### Access the site
Once the setup is complete, you can access the Drupal site at `http://drupal-cms-ai-demo-flowdrop.ddev.site` and log in with username `admin` and password `admin`.

## Develop Flowdrop Agents Applications

Before running the setup command, make sure you have your OpenAI API key configured (see "Set up your OpenAI API key" above). If not set, you will be prompted to enter it during setup.

1. Run `ddev setup-flowdrop-dev` to set up the Flowdrop development environment.
   - This applies the AI recipe (using your `OPENAI_API_KEY` or prompting for one)
   - Enables AI explorer, logging, and observability modules
2. Your actual modules will be in the `modules/` directory, but symlinked into `web/modules/contrib/` for Drupal to find them.
3. DO NOT add this module to the repo. Keep it local only for development purposes.

## FlowDrop source + build workflow

If you need to modify FlowDrop itself (Svelte + @xyflow/svelte), see:

- `docs/flowdrop-build.md`
