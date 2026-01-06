# Current Branch Context

Per-branch tracking for the current work session.

## Current Branches

| Repo | Branch | Issue | Status |
|------|--------|-------|--------|
| Demo site (this repo) | `main` | - | - |
| flowdrop_ui_agents | `3563756-support-multi-agents-and` | [#3563756](https://www.drupal.org/project/flowdrop_ui_agents/issues/3563756) | COMPLETED - MR Created |

## Next Issue

**[#3564034](https://www.drupal.org/project/flowdrop_ui_agents/issues/3564034)** - General Improvements to handling Tools and Chats

### To Start Work on #3564034

1. Go to: https://www.drupal.org/project/flowdrop_ui_agents/issues/3564034
2. Create the issue fork (look in sidebar)
3. Run these commands:

```bash
cd modules/flowdrop_ui_agents

# Add the issue fork remote
git remote add flowdrop_ui_agents-3564034 git@git.drupal.org:issue/flowdrop_ui_agents-3564034.git

# Fetch and create local branch
git fetch flowdrop_ui_agents-3564034
git checkout -b 3564034-tools-and-chats-ux flowdrop_ui_agents-3564034/1.0.x

# Verify you're on the new branch
git branch --show-current
```

4. Update this file with the actual branch name

## Active Plans

| Issue | Plan File | Status |
|-------|-----------|--------|
| #3563756 | `.claude/plans/do-3563756-support-multi-agents-and.md` | COMPLETED |
| #3564034 | `.claude/plans/do-3564034-tools-and-chats-ux.md` | Not Started |

## Completed Work (#3563756)

- Sub-agent saving with topological sort
- 32 Kernel tests (370 assertions)
- Fixed: sub-agent description not saving from assistant editor

## Test URLs

- **Agent**: `/admin/config/ai/agents/agent_bundle_lister/edit_with/flowdrop_agents`
- **Assistant**: `/admin/config/ai/ai-assistant/bundle_lister_assistant/edit-flowdrop`
- **Standard Form (for comparison)**: `/admin/config/ai/agents/agent_bundle_lister/edit`

## Quick Commands

```bash
# Check module branch
cd modules/flowdrop_ui_agents && git branch --show-current

# See changes on branch (compare to 1.0.x)
cd modules/flowdrop_ui_agents && git log --oneline 1.0.x..HEAD

# Run tests
ddev exec "SIMPLETEST_DB=sqlite://localhost/sites/default/files/.sqlite ./vendor/bin/phpunit -c web/core/phpunit.xml.dist modules/flowdrop_ui_agents/tests/src/Kernel/ --colors=always"

# Clear Drupal cache after changes
ddev drush cr
```

## Session Notes

### 2024-12-19
- Completed issue #3563756 - sub-agent saving fix
- Created MR on drupal.org
- Created plan for new issue #3564034
- Next: Create issue fork branch for #3564034

### 2024-12-18
- Set up Claude documentation structure
- Created plan for issue #3563756
- Implemented Kernel tests

---

*Last updated: 2024-12-19*
