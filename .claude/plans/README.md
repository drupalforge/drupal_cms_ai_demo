# Plans Directory

This directory contains implementation plans linked to specific issues.

## Naming Convention

Plans are named to match the branch name in the module repo:

```
{source}-{issue-number}-{short-description}.md
```

### Sources

| Prefix | Issue Tracker | URL |
|--------|---------------|-----|
| `do` | Drupal.org (flowdrop_ui_agents) | https://www.drupal.org/project/issues/flowdrop_ui_agents |
| `gh` | GitHub (this demo repo) | https://github.com/drupalforge/drupal_cms_ai_demo_flowdrop/issues |

### Examples

- `do-3512345-add-deepchat-node.md` - Matches branch `3512345-add-deepchat-node`
- `gh-42-update-demo-config.md` - GitHub issue #42

### Finding the Issue Number

The drupal.org branch name format is `{issue-number}-{description}`:

```bash
cd modules/flowdrop_ui_agents
git branch --show-current
# Output: 3512345-add-deepchat-node
# Issue: https://www.drupal.org/project/flowdrop_ui_agents/issues/3512345
```

## Plan Template

```markdown
# Issue Title

- **Issue**: [#XXXXXXX](link-to-issue)
- **Branch**: `issue-XXXXXXX-description`
- **Status**: Planning | In Progress | Review | Complete

## Goal

What this plan accomplishes.

## Tasks

- [ ] Task 1
- [ ] Task 2

## Technical Notes

Implementation notes, findings, and decisions made during development.
Keep all technical discoveries here rather than in separate files.

## Blockers / Questions

Any blockers or questions that need resolution.
```

## Active Plans

- `do-3563756-support-multi-agents-and.md` - Multi-agent save fix and polish
