# Current Branch Context

Per-branch tracking for the current work session.

## Current Branches

| Repo | Branch | Issue |
|------|--------|-------|
| Demo site (this repo) | `main` | - |
| flowdrop_ui_agents | `3565644-bring-chatbots-into` | [#3565644](https://www.drupal.org/project/flowdrop_ui_agents/issues/3565644) |

## Active Work

**Issue #3565644 - Bring Chatbots into FlowDrop UI**: Add DeepChat chatbot nodes to the FlowDrop UI, allowing users to visually connect chatbots to assistants.

See: `.claude/plans/current/do-3565644-bring-chatbots-into.md`

## Test URLs

- **Assistant Editor**: `/admin/config/ai/ai-assistant/drupal_cms_assistant/edit-flowdrop`
- **API Endpoint**: `/api/flowdrop-agents/nodes`

## Quick Commands

```bash
# Check module branch
cd modules/flowdrop_ui_agents && git branch --show-current

# See changes on branch (compare to 1.0.x)
cd modules/flowdrop_ui_agents && git log --oneline 1.0.x..HEAD

# Push module to drupal.org
cd modules/flowdrop_ui_agents && git push flowdrop_ui_agents-3565644 3565644-bring-chatbots-into

# Clear Drupal cache after changes
ddev drush cr
```

---

*Last updated: 2026-01-07*
