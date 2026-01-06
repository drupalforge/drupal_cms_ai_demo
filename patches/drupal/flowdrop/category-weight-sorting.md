# Category Weight-Based Sorting for Sidebar

**Module**: drupal/flowdrop
**Component**: flowdrop_ui (Sidebar)
**Type**: Feature / Patch

## Upstream Issue

- **Issue**: https://www.drupal.org/project/flowdrop/issues/XXXXXXX ← UPDATE AFTER SUBMISSION
- **Merge Request**: https://git.drupalcode.org/project/flowdrop/-/merge_requests/XX ← UPDATE AFTER SUBMISSION
- **Status**: [ ] Submitted [ ] Accepted [ ] Merged

## Problem

The FlowDrop sidebar sorts categories alphabetically, ignoring any sort order provided by the backend API. This makes it impossible for integrating modules to control category display order.

For example, in `flowdrop_ui_agents`, we want "Sub-Agent Tools" to appear first (before "Drupal Core Actions"), but alphabetical sorting puts it near the bottom.

## Solution

Add support for `categoryWeight` on nodes. The sidebar reads this value and sorts categories by weight (ascending) before falling back to alphabetical.

### How It Works

1. Backend API includes `categoryWeight` on each node (e.g., `-100` for agents, `0` for tools)
2. Sidebar extracts unique categories from nodes
3. Sidebar builds a weight map: `category → weight`
4. Categories sort by weight first, then alphabetically within same weight

### Weight Convention

| Weight | Category Type |
|--------|---------------|
| -100 | Agents, Assistants |
| -90 | Chatbots |
| -80 | Search/RAG |
| 0 | Standard tools (default) |
| 100 | Other/Uncategorized |

## Files Modified

- `modules/flowdrop_ui/build/flowdrop/flowdrop.iife.js` - IIFE build (used by Drupal)
- `modules/flowdrop_ui/build/flowdrop/flowdrop.es.js` - ES module build (for bundlers)

## Patch Notes

The patch modifies the `getCategories()` function in the sidebar component to:

1. Build a `Map` of category → weight from nodes
2. Sort using weight comparison, falling back to `localeCompare()`

**Important**: The IIFE build requires an IIFE wrapper for `const` declarations since the code is in expression context.

## Testing

1. Apply patch to FlowDrop module
2. Ensure your API returns nodes with `categoryWeight` property
3. Clear Drupal caches: `drush cr`
4. Hard refresh browser
5. Verify sidebar categories appear in weight order

## Removal Checklist

Once accepted upstream:

- [ ] Remove from `patches.json`
- [ ] Delete `category-weight-sorting.patch`
- [ ] Delete `category-weight-sorting.md`
- [ ] Update FlowDrop to version containing the fix
