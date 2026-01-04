# Current Branch Context

Per-branch tracking for the current work session.

## Current Branches

| Repo | Branch | Issue |
|------|--------|-------|
| Demo site (this repo) | `poc/flowdrop-ui-agents` | - |
| flowdrop_ui_agents | `3564034-general-improvements-to` | [#3564034](https://www.drupal.org/project/flowdrop_ui_agents/issues/3564034) |

## Active Plan

`.claude/plans/do-3564034-general-improvements-to.md`

## Current Focus

**Sidebar Component Display Issues** - Fix "Agent_tools" label and restore green icon/color styling for agents in sidebar.

## Test URLs

- **Assistant Editor**: `/admin/config/ai/ai-assistant/drupal_cms_assistant/edit-flowdrop`
- **API Endpoint**: `/api/flowdrop-agents/nodes/by-category`

## Quick Commands

```bash
# Check module branch
cd modules/flowdrop_ui_agents && git branch --show-current

# See changes on branch (compare to 1.0.x)
cd modules/flowdrop_ui_agents && git log --oneline 1.0.x..HEAD

# Push to issue fork
cd modules/flowdrop_ui_agents && git push flowdrop_ui_agents-3564034 3564034-general-improvements-to

# Clear Drupal cache after changes
ddev drush cr
```

## Session Notes

### 2025-01-03
- Switched to issue #3564034 (sidebar improvements)
- Issues: "Agent_tools" label, green styling regression

### 2024-12-18
- Set up new Claude documentation structure
- Created plan for issue #3563756
- Completed: Fix multi-agent save

---

*Last updated: 2025-01-03*
