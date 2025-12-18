# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Repository Structure

This is a **demo site repository** hosted on GitHub that:
- Builds a complete Drupal CMS demo on DrupalForge
- Showcases AI + FlowDrop integration
- Contains `flowdrop_ui_agents` module for **local development only**

The `flowdrop_ui_agents` module has its **own git repository** on drupal.org GitLab. Development on that module should follow Drupal.org workflow (issue first, then feature branch).

Do not do things that require AUTH. Generally Assume the user has to input git commands and give the commands to the User to do themselves (make sure we're pushing to the right place)

This repo has two build processes. Live via DevPanel and scripts in .DevPanel and locally via DDEV and the command ddev setup-flowdrop-dev

## Two-Repo Workflow

```
┌─────────────────────────────────────────┐
│  This Repo (GitHub)                     │
│  drupal_cms_ai_demo_flowdrop            │
│                                         │
│  Purpose: Demo site, DrupalForge deploy │
│  Branch: main                           │
└─────────────────────────────────────────┘
              │
              │ ddev setup-flowdrop-dev
              ▼
┌─────────────────────────────────────────┐
│  modules/flowdrop_ui_agents/ (cloned)   │
│  ↓ symlinked to                         │
│  web/modules/contrib/flowdrop_ui_agents │
│                                         │
│  Origin: git.drupal.org                 │
│  Workflow: Issue → Feature branch → MR  │
└─────────────────────────────────────────┘
```

## Development Environment

Uses DDEV for local development:

```bash
ddev start                    # Start environment
ddev drush cr                 # Clear cache
ddev drush cex                # Export configuration
ddev drush cim                # Import configuration
```

Site URL: `http://drupal-cms-ai-demo-flowdrop.ddev.site`
Default credentials: admin / admin

## FlowDrop Module Development

```bash
# Set up development environment (clones flowdrop_ui_agents into modules/)
ddev setup-flowdrop-dev

# The module is now at modules/flowdrop_ui_agents/
# It has its own .git - work there for drupal.org contributions
```

### Drupal.org Workflow for flowdrop_ui_agents

1. Create an issue on drupal.org/project/flowdrop_ui_agents
2. Create feature branch: `git checkout -b issue-XXXXXXXX-description`
3. Develop and test locally
4. Push to drupal.org GitLab
5. Create Merge Request referencing the issue

## Key Directories

| Directory | Purpose |
|-----------|---------|
| `modules/` | Local dev modules (not committed to this repo) |
| `web/modules/contrib/` | Composer-managed modules |
| `recipes/` | Drupal CMS recipes |
| `config/sync/` | Configuration export |
| `.claude/` | Claude Code documentation |

## Issue Trackers

| Project | Issues |
|---------|--------|
| flowdrop_ui_agents | https://www.drupal.org/project/issues/flowdrop_ui_agents |
| This demo repo | https://github.com/drupalforge/drupal_cms_ai_demo_flowdrop/issues |

## Claude Documentation Structure

```
.claude/
├── branch.md              # Current branch context (update when switching)
├── branch-review.md       # For review agent (wiped each review)
├── plans/                 # Issue-linked implementation plans
│   ├── README.md          # Naming conventions & template
│   └── do-XXXXX-*.md      # Drupal.org issue plans
├── screenshots/           # Screenshots for AI to analyze
├── flowdrop-ui-agents.md  # Module architecture reference
└── planning.md            # Historical roadmap & phases
```

### Multi-Agent Workflow

1. **Primary agent** - Works on branch, updates `branch.md`, creates/updates plan
2. **Review agent** - Reviews work, documents findings in `branch-review.md`
3. Technical notes go **in the plan file**, not separate files
4. Screenshots go in `.claude/screenshots/` for AI analysis
