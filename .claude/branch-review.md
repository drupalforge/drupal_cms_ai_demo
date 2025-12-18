# Branch Review

**This file is wiped for each new review.**

A second AI agent uses this file to review work done by the primary agent.

## How This Works

1. **Primary agent** works on the branch, makes changes, updates `branch.md`
2. **Review agent** is invoked to check the work
3. Review agent documents findings here
4. Primary agent addresses feedback
5. **File is cleared** when review is complete and branch is merged

## For the Review Agent

You are acting as a code reviewer. Your job is to:

1. **Check the diff** - What changed since the branch was created?
2. **Verify against the plan** - Does the work match the plan in `.claude/plans/`?
3. **Look for issues** - Bugs, security problems, missing edge cases
4. **Check Drupal standards** - Coding standards, proper hooks, etc.

### Quick Commands

```bash
# See what branch the module is on
cd modules/flowdrop_ui_agents && git branch --show-current

# See all changes on this branch vs main development branch
git log --oneline 1.0.x..HEAD
git diff 1.0.x..HEAD

# Find the related plan (issue number is start of branch name)
ls -la ../../.claude/plans/
```

---

## Current Review

**Branch**: -
**Issue**: -
**Plan**: -
**Reviewer**: -
**Date**: -

### Summary of Changes

(To be filled by reviewer)

### Findings

(To be filled by reviewer)

### Checklist

- [ ] Changes match the issue/plan description
- [ ] No unrelated changes included
- [ ] Code follows Drupal coding standards
- [ ] No security issues (SQL injection, XSS, etc.)
- [ ] Error handling is appropriate

### Status

- [ ] Review in progress
- [ ] Changes requested
- [ ] Approved
