# General Improvements to Handling Tools and Chats

- **Issue**: [#3564034](https://www.drupal.org/project/flowdrop_ui_agents/issues/3564034)
- **Branch**: `3564034-tools-and-chats-ux` (to be created)
- **Status**: Not Started

## Getting Started

### Create Issue Fork Branch

1. Go to: https://www.drupal.org/project/flowdrop_ui_agents/issues/3564034
2. Look for "Issue fork" in the sidebar and create it
3. Drupal.org will provide commands similar to:

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

4. Update `.claude/branch.md` with the new branch name once created

---

## Goal

Improve the UX for handling tools in the FlowDrop editor, including better organization, automatic attachment, proper spacing, and enhanced configuration options.

## Tasks

### 1. Tool Drawer Categories
**Match Select Tools Widget categories**

The Tool Drawer (sidebar) should use the same category organization as the existing Select Tools Widget in the standard Drupal form.

- [ ] Investigate how Select Tools Widget categorizes tools
- [ ] Update Tool Drawer to use same category structure
- [ ] Ensure consistent labeling/ordering

### 2. Auto-Attach on Drag
**Drag tool onto Agent = automatic connection**

When you drag a tool from the drawer onto an Agent node:
- [ ] Detect drop target is an Agent node
- [ ] Automatically create edge connection
- [ ] Position tool in reasonable location relative to other tools

### 3. Initial Tool Spacing
**Fix overlapping tools on first load**

Tools currently overlap slightly when first loaded.

- [ ] Investigate current positioning logic
- [ ] Add proper spacing between tools
- [ ] Consider grid-based or radial layout from parent agent

### 4. RAG Search Tools
**Add RAG search tools with index linking**

- [ ] Add RAG Search tools to the tool drawer
- [ ] Link to relevant indexes
- [ ] Ensure proper configuration options

### 5. Node Config Panel Ordering
**Match form field priorities**

The editing panel for a node should have similar field ordering and priorities as the standard Drupal form:
- [ ] Audit current panel field order
- [ ] Compare to standard form order
- [ ] Hide advanced features by default (collapsible?)
- [ ] Prioritize commonly-used fields at top

### 6. Tool Config Improvements
**Force values and better config UI**

- [ ] Add ability to "force" values on tool configuration
- [ ] Improve overall config panel UX
- [ ] Consider which fields should be editable vs locked

---

## Technical Notes

### Key Files

| File | Purpose |
|------|---------|
| `js/flowdrop-agents-editor.js` | Editor initialization, drag/drop handling |
| `js/components/ToolDrawer.js` | Tool sidebar component (if exists) |
| `src/Service/ToolDiscovery.php` | Tool categorization (if exists) |

### Related Components

- FlowDrop library handles canvas/drag behavior
- Tool plugins come from `drupal/ai` and `drupal/tool` modules
- Categories may be defined in plugin annotations

## Test URLs

- **Assistant Editor**: `/admin/config/ai/ai-assistant/bundle_lister_assistant/edit-flowdrop`
- **Agent Editor**: `/admin/config/ai/agents/agent_bundle_lister/edit_with/flowdrop_agents`
- **Standard Tools Widget**: `/admin/config/ai/agents/agent_bundle_lister/edit` (for comparison)

## Running Tests

```bash
ddev exec "SIMPLETEST_DB=sqlite://localhost/sites/default/files/.sqlite ./vendor/bin/phpunit -c web/core/phpunit.xml.dist modules/flowdrop_ui_agents/tests/src/Kernel/ --colors=always"
```

## Session Log

*No sessions yet*

---

*Created: 2024-12-19*
