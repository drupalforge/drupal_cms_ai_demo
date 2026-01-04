# Document JavaScript Build Files (IIFE vs ES Modules)

**Module**: drupal/flowdrop
**Component**: Documentation (README)
**Type**: Documentation
**Priority**: Normal

## Upstream Issue

- **Issue**: https://www.drupal.org/project/flowdrop/issues/XXXXXXX ← UPDATE AFTER SUBMISSION
- **Merge Request**: https://git.drupalcode.org/project/flowdrop/-/merge_requests/XX ← UPDATE AFTER SUBMISSION
- **Status**: [ ] Submitted [ ] Accepted [ ] Merged

## Problem

FlowDrop ships two JavaScript builds but doesn't document which one Drupal uses:

```
modules/flowdrop_ui/build/flowdrop/
├── flowdrop.es.js    # ES Modules format
└── flowdrop.iife.js  # IIFE format
```

This causes confusion when developers need to patch FlowDrop behavior. They may patch the wrong file (`.es.js`) and wonder why their changes don't take effect.

### Real-World Example

When developing `flowdrop_ui_agents`, we needed to patch the sidebar sorting logic. We initially patched `flowdrop.es.js` because:

1. It's the "modern" format, suggesting it's the main file
2. The code is more readable (not as minified)
3. ES modules are the current JavaScript standard

However, Drupal's library system uses traditional `<script>` tags, which load the IIFE build. Our patch had no effect until we realized this and patched `flowdrop.iife.js` instead.

## Solution

Add a section to the FlowDrop README documenting:

1. What each build file is for
2. Which one Drupal uses
3. Which one to patch for Drupal integration

## Proposed README Addition

```markdown
## JavaScript Build Files

FlowDrop UI ships two pre-built JavaScript bundles:

| File | Format | Used By |
|------|--------|---------|
| `flowdrop.iife.js` | IIFE (Immediately Invoked Function Expression) | **Drupal** - loaded via library system |
| `flowdrop.es.js` | ES Modules (`import`/`export`) | Bundler-based projects (Vite, Webpack) |

### Which File Does Drupal Use?

Drupal's asset system uses traditional `<script>` tags, not ES modules. Therefore, 
**Drupal loads `flowdrop.iife.js`**.

### Patching FlowDrop in Drupal

If you need to patch FlowDrop behavior for a Drupal site:

1. Patch `flowdrop.iife.js` (not `.es.js`)
2. Clear Drupal caches: `drush cr`
3. Hard refresh browser to bypass JS cache

To verify which file is loaded, check browser DevTools → Network tab → filter by "flowdrop".
```

## Files Modified

- `README.md` (root of flowdrop module)

## Upstream Submission

### Issue Title
Document which JavaScript build file Drupal uses (IIFE vs ES modules)

### Issue Description
(Copy the Problem and Solution sections above)

### Tags
- documentation
- developer experience
- flowdrop_ui

## Removal Checklist

Once accepted upstream:

- [ ] Remove from `patches.json` (if added)
- [ ] Delete `document-build-files.patch`
- [ ] Delete `document-build-files.md`
- [ ] Update FlowDrop to version containing the fix
