# Plans Directory

Implementation plans linked to specific issues in the module repository.

## Folder Structure

```
plans/
├── current/     # Active work - one plan per branch being worked on
├── future/      # Planned work not yet started
└── archived/    # Completed or abandoned work
```

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

- `do-3565644-bring-chatbots-into.md` - Matches branch `3565644-bring-chatbots-into`
- `gh-42-update-demo-config.md` - GitHub issue #42

### Finding the Issue Number

The drupal.org branch name format is `{issue-number}-{description}`:

```bash
cd modules/flowdrop_ui_agents
git branch --show-current
# Output: 3565644-bring-chatbots-into
# Issue: https://www.drupal.org/project/flowdrop_ui_agents/issues/3565644
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

## Workflow

1. **Starting work**: Create plan in `current/`, switch to branch in module
2. **During work**: Update plan with technical notes and task progress
3. **Completing work**: Move plan to `archived/` after MR is merged
4. **Future ideas**: Add plans to `future/` for work not yet started
