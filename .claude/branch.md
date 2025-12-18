# Current Branch Context

Per-branch tracking for the current work session.

## Current Branches

| Repo | Branch | Issue |
|------|--------|-------|
| Demo site (this repo) | `main` | - |
| flowdrop_ui_agents | `3563756-support-multi-agents-and` | [#3563756](https://www.drupal.org/project/flowdrop_ui_agents/issues/3563756) |

## Active Plan

`.claude/plans/do-3563756-support-multi-agents-and.md`

## Current Focus

**Fixing Save for Multi-Agent Assistants** - Save is broken when an Assistant has multiple sub-agents.

## Test URLs

- **Agent**: `/admin/config/ai/agents/agent_bundle_lister/edit_with/flowdrop_agents`
- **Assistant**: `/admin/config/ai/ai-assistant/bundle_lister_assistant/edit-flowdrop`

## Quick Commands

```bash
# Check module branch
cd modules/flowdrop_ui_agents && git branch --show-current

# See changes on branch (compare to 1.0.x)
cd modules/flowdrop_ui_agents && git log --oneline 1.0.x..HEAD

# Push to issue fork
cd modules/flowdrop_ui_agents && git push flowdrop_ui_agents-3563756 3563756-support-multi-agents-and

# Clear Drupal cache after changes
ddev drush cr
```

## Session Notes

### 2024-12-18
- Set up new Claude documentation structure
- Created plan for issue #3563756
- Current task: Fix multi-agent save

---

*Last updated: 2024-12-18*
