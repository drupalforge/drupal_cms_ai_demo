# FlowDrop UI Agents - Sidebar Component Display Issues

- **Issue**: [#3564034](https://www.drupal.org/project/flowdrop_ui_agents/issues/3564034)
- **Branch**: `3564034-general-improvements-to`
- **Status**: In Progress

## Issue Text

> We need to make the Tool Drawer use the same Categories as the Select Tools Widget
> When you drag a tool on top of an Agent, it should automatically attach it and put it in a nice reasonable location relative to the others
> The tools when you load them first time all overlap a little and so need more spacing
> We need to add RAG search tools and see how they work (Should link you to the indexes)
> We need to make the forms in the editing a node have similar order and priorities as they do in the form (So advanced features can be hidden)
> We need to improve the Config on a tool. incude the ability to force values

## Problem Summary

Three issues with how components are displayed in the FlowDrop sidebar at `/admin/config/ai/ai-assistant/{assistant}/edit-flowdrop`:

| Issue | Status | Description |
|-------|--------|-------------|
| Agent_tools name is ugly | ✅ FIXED | Changed to "Sub-Agent Tools" (with leading space for sort) |
| Agent_tools position at top | ✅ GOOD | Maintained via leading space + uksort weights |
| Agent_tools icon/color wrong | ✅ FIXED | Changed to Teal (#14b8a6), JS applies styling |

**Bonus features added** (not in original scope):
| Feature | Status | Description |
|---------|--------|-------------|
| Auto-attach tools | ✅ NEW | Tools auto-connect to nearest agent on drop |
| Magnetic drag | ✅ NEW | Moving agent drags connected tools with it |
| RAG Index Tool | ⚠️ NEW | Search API integration - **should be separate issue** |

---

## Issue 1: "Agent_tools" Label Should Be "Sub-Agent Tools"

### Current Behavior
The sidebar category header displays "Agent_tools" with an underscore.

### Expected Behavior
Should display "Sub-Agent Tools" to match the standard Drupal agent form.

### Root Cause Analysis

**Source of the hardcoded "Agent_tools":**

1. `src/Controller/Api/NodesController.php` line 101-102:
```php
// use 'Agent_tools' key (Capitalized) to ensuring sorting to top (ASCII 'A' < 'D').
$toolsByCategory = ['Agent_tools' => $agents] + $toolsByCategory;
```

2. `src/Service/AgentWorkflowMapper.php` line 883:
```php
'category' => 'Agent_tools',
```

**Where the CORRECT label exists (in ai_agents module):**

`web/modules/contrib/ai_agents/src/Plugin/AiFunctionGroup/AgentTools.php` lines 14-17:
```php
#[FunctionGroup(
  id: 'agent_tools',
  group_name: new TranslatableMarkup('Sub-Agent Tools'),
  description: new TranslatableMarkup('These exposes agents as tools...'),
  weight: -10,
)]
```

**Existing JS workaround (incomplete):**

`js/flowdrop-agents-editor.js` lines 770-771 attempts to rename it but uses wrong label:
```javascript
if (textSpan) {
    textSpan.textContent = 'Agent Tools'; // Still not using the correct "Sub-Agent Tools" label!
}
```

### Fix Required

**Option A: Hardcode the correct label (simple, acceptable)**

Change these 3 locations:

1. `src/Controller/Api/NodesController.php` line 102:
```php
// BEFORE:
$toolsByCategory = ['Agent_tools' => $agents] + $toolsByCategory;

// AFTER:
$toolsByCategory = ['Sub-Agent Tools' => $agents] + $toolsByCategory;
```

2. `src/Service/AgentWorkflowMapper.php` line 883:
```php
// BEFORE:
'category' => 'Agent_tools',

// AFTER:
'category' => 'Sub-Agent Tools',
```

3. `js/flowdrop-agents-editor.js` update the matching logic (lines 764, 771):
```javascript
// BEFORE:
if (summary.textContent.trim().includes('Agent_tools')) {

// AFTER:
if (summary.textContent.trim().includes('Sub-Agent Tools')) {
```

And remove line 771 since it's no longer needed (or keep for safety).

**Option B: Pull label from FunctionGroup metadata (preferred, programmatic)**

Already working for tools in `AgentWorkflowMapper::getToolCategory()`. Apply same pattern:

```php
// In NodesController or AgentWorkflowMapper
$groupDef = $this->functionGroupPluginManager->getDefinition('agent_tools');
$agentCategoryLabel = (string) ($groupDef['group_name'] ?? 'Sub-Agent Tools');
```

This ensures the label stays in sync with the ai_agents module's definition.

---

## Issue 2: Agent_tools Icon/Color Regression

### Current Behavior
Agent items in the sidebar now look like regular orange tools instead of having special green styling.

### Expected Behavior
Agent items should have:
- Green icon background (`#10b981` / Green-500)
- Agent icon (`mdi:face-agent`)
- Green accent styling on the category header

### Root Cause Analysis

**The API IS returning correct values:**

`src/Service/AgentWorkflowMapper.php` lines 883-885:
```php
'icon' => 'mdi:face-agent',
'color' => 'var(--color-ref-green-500)',
```

**CSS styling IS defined correctly:**

`css/flowdrop-agents-editor.css` lines 204-224:
```css
.flowdrop-category-agents > summary {
    background-color: #ecfdf5 !important; /* Green-50 */
    border-left: 3px solid #10b981 !important; /* Green-500 */
    border-radius: 4px;
    margin-bottom: 4px;
}

.flowdrop-category-agents > summary .flowdrop-node-icon {
    background-color: #10b981 !important; /* Green-500 */
    color: white !important;
}
```

**JS class application logic:**

`js/flowdrop-agents-editor.js` lines 760-775:
```javascript
if (summary.textContent.trim().includes('Agent_tools')) {
    const details = summary.closest('details');
    if (details && !details.classList.contains('flowdrop-category-agents')) {
        details.classList.add('flowdrop-category-agents');
    }
}
```

**The problem is likely:**
1. The text matching is looking for `'Agent_tools'` but after Issue 1 is fixed, it will be `'Sub-Agent Tools'`
2. OR the FlowDrop UI core changed the HTML structure so the CSS selectors no longer match
3. OR the FlowDrop UI core is not using the `icon`/`color` from the API response for sidebar items

### Investigation Steps

1. **Check if `.flowdrop-category-agents` class is being applied:**
   - Open DevTools in browser
   - Inspect the sidebar
   - Look for the `<details>` element containing agents
   - Check if it has class `flowdrop-category-agents`

2. **Check API response:**
   - Call `GET /api/flowdrop-agents/nodes/by-category` directly in browser/curl
   - Verify the response has agents with `type: 'agent'`, correct `icon` and `color`

3. **Check FlowDrop core rendering:**
   - See if FlowDrop UI module was updated recently
   - Check if the sidebar HTML structure changed (different class names, element hierarchy)

### Fix Required

**If class IS being applied but styling doesn't work:**
- FlowDrop core may have changed HTML structure
- Update CSS selectors in `css/flowdrop-agents-editor.css` to match new structure

**If class is NOT being applied:**
- Update the text match in `js/flowdrop-agents-editor.js:764` to use the new label `'Sub-Agent Tools'`

**If individual agent items need styling (not just category header):**
- The FlowDrop sidebar may need to use the `type` field from API to apply styling
- May need to add JS that adds classes to individual items based on their `type: 'agent'`

---

## Issue 3: Tool Groupings Already Come From Metadata (No Fix Needed)

### Current Implementation

Tool categories ARE already being pulled programmatically from the `FunctionGroup` plugin metadata.

Reference: `src/Service/AgentWorkflowMapper.php` lines 985-1017:
```php
protected function getToolCategory(string $toolId): string
{
    try {
        $definition = $this->functionCallPluginManager->getDefinition($toolId);
        $groupId = $definition['category'] ?? $definition['group'] ?? NULL;

        if ($groupId) {
            if ($this->functionGroupPluginManager->hasDefinition($groupId)) {
                $groupDef = $this->functionGroupPluginManager->getDefinition($groupId);
                // 'group_name' is the label key (confirmed via debug)
                $label = $groupDef['group_name'] ?? $groupDef['label'] ?? $groupDef['name'] ?? ucfirst($groupId);
                return (string) $label;
            }
        }
    } catch (\Exception $e) {
        // Fall through to default
    }
    return 'Other';
}
```

This is working correctly for regular tools. The only issue is that **agents bypass this logic** and have a hardcoded category.

---

## Implementation Tasks for Google Gemini

### Phase 1: Fix the Label (Priority: HIGH)

1. **File: `src/Controller/Api/NodesController.php`**
   - Line 102: Change `'Agent_tools'` to `'Sub-Agent Tools'`

2. **File: `src/Service/AgentWorkflowMapper.php`**
   - Line 883: Change `'Agent_tools'` to `'Sub-Agent Tools'`

3. **File: `js/flowdrop-agents-editor.js`**
   - Line 764: Change text match from `'Agent_tools'` to `'Sub-Agent Tools'`
   - Line 771: Can be removed or updated to just be a safety fallback

### Phase 2: Fix the Icon/Color (Priority: HIGH)

1. **Debug first:**
   - Add `console.log` in `setupSidebarStyling()` to see if the function is finding the category header
   - Check if the text content after Phase 1 changes matches properly

2. **File: `js/flowdrop-agents-editor.js`**
   - If needed, update the DOM selection logic in `setupSidebarStyling()` function (lines 755-785)
   - May need to style individual agent items, not just the category header

3. **File: `css/flowdrop-agents-editor.css`**
   - If FlowDrop core changed HTML, update selectors to match
   - Consider using more specific selectors like `[data-category="Sub-Agent Tools"]` if FlowDrop provides data attributes

### Phase 3: (Optional) Use Metadata for Agent Category Label

If time permits, inject `FunctionGroupPluginManager` into the controller and pull the label dynamically:

```php
// In NodesController constructor
protected FunctionGroupPluginManager $functionGroupPluginManager

// In getNodesByCategory()
$groupDef = $this->functionGroupPluginManager->getDefinition('agent_tools');
$agentLabel = (string) ($groupDef['group_name'] ?? 'Sub-Agent Tools');
$toolsByCategory = [$agentLabel => $agents] + $toolsByCategory;
```

---

## Files Modified (Actual)

| File | Planned | Actual Changes |
|------|---------|----------------|
| `src/Controller/Api/NodesController.php` | Label change | ✅ Done + refactored sorting |
| `src/Service/AgentWorkflowMapper.php` | Label change | ✅ Done + FunctionGroupPluginManager + expanded categories |
| `js/flowdrop-agents-editor.js` | Text matching | ✅ Done + setupSidebarStyling() + setupAutoAttach() + setupMagneticDrag() |
| `css/flowdrop-agents-editor.css` | Maybe | ✅ Done - Teal styling added |
| `flowdrop_ui_agents.services.yml` | Not planned | ✅ Added FunctionGroupPluginManager |
| `flowdrop_ui_agents.libraries.yml` | Not planned | ✅ Version bump to 1.3 |
| `src/Plugin/AiFunctionCall/RagIndexTool.php` | Not planned | ✅ STASHED to `.claude/stashed/rag-plugin/` |
| `src/Plugin/Deriver/RagIndexToolDeriver.php` | Not planned | ✅ STASHED to `.claude/stashed/rag-plugin/` |

---

## Testing Checklist

**Priority: Get the core issues working before committing. Auto-attach and magnetic drag are bonus features.**

### Must Work Before Commit (Original Issues)
1. Clear caches: `ddev drush cr`
2. Visit `/admin/config/ai/ai-assistant/drupal_cms_assistant/edit-flowdrop`
3. Verify:
   - [ ] Sidebar shows "Sub-Agent Tools" as category header (not "Agent_tools")
   - [ ] Category header has teal styling (teal left border, light teal background)
   - [ ] Individual agent icons show teal background with `mdi:face-agent` icon
   - [ ] "Sub-Agent Tools" category remains at TOP of sidebar list (before all tool categories)
   - [ ] Regular tool categories still show their correct colors and icons

### Nice-to-Have (Bonus Features)
   - [ ] Auto-attach works when dropping tools near agents
   - [ ] Magnetic drag moves tool children when dragging agents

---

## Code Analysis (2025-01-03)

### Summary of Uncommitted Changes

There are significant uncommitted changes across 7 files plus 2 new plugin files (untracked):

| File | Lines Changed | Status |
|------|---------------|--------|
| `css/flowdrop-agents-editor.css` | +31 | Modified |
| `flowdrop_ui_agents.libraries.yml` | +1 | Modified (version bump) |
| `flowdrop_ui_agents.services.yml` | +1 | Modified |
| `js/flowdrop-agents-editor.js` | +320 | Modified (major) |
| `src/Controller/Api/NodesController.php` | +30 | Modified |
| `src/Service/AgentWorkflowMapper.php` | +50 | Modified |
| `src/Service/WorkflowParser.php` | ? | Modified |
| `src/Plugin/AiFunctionCall/RagIndexTool.php` | +134 | **NEW** (untracked) |
| `src/Plugin/Deriver/RagIndexToolDeriver.php` | +76 | **NEW** (untracked) |

---

### Analysis by Component

#### 1. Label Fix: `Agent_tools` → ` Sub-Agent Tools` ✅ DONE

**Implementation Approach**: Uses a leading space (` Sub-Agent Tools`) for ASCII sorting to ensure agents appear first alphabetically.

**Files Changed**:
- `NodesController.php:101-103` - Category key changed
- `AgentWorkflowMapper.php:885` - Agent category value updated
- `AgentWorkflowMapper.php:66-67` - Added color mappings for both ` Sub-Agent Tools` and `Sub-Agent Tools`

**Verdict**: ✅ Good approach. The leading space trick is clever but slightly fragile. If FlowDrop ever trims category names, it could break. A more robust solution would use weighted sorting (which is also partially implemented in `getToolsByCategory()`).

**Recommendation**: The dual-approach (leading space + `uksort` with weights) is redundant. Pick one. The `uksort` approach in `getToolsByCategory()` is cleaner and more maintainable.

---

#### 2. Color Change: Green → Teal ✅ DONE

**Implementation**:
- CSS changed from `#10b981` (Green-500) to `#14b8a6` (Teal-500)
- PHP constant `CATEGORY_COLORS` updated with teal for agents

**Files Changed**:
- `css/flowdrop-agents-editor.css:198-225` - New teal-based styling
- `AgentWorkflowMapper.php:65` - Changed `'agent' => 'var(--color-ref-teal-500)'`

**Verdict**: ✅ Clean change. Teal differentiates agents from success/output (green) semantically.

---

#### 3. Sidebar Styling JS: `setupSidebarStyling()` ✅ DONE

**Implementation** (new function ~70 lines):
- Queries for `.flowdrop-details__summary` elements
- Text-matches `'Sub-Agent Tools'` to find agent category
- Applies inline styles AND CSS class `flowdrop-category-agents`
- Overrides icon using CSS mask-image with Iconify URL
- Runs on interval (every 2s) to handle lazy loading

**Verdict**: ⚠️ Mixed feelings.

**Positives**:
- Comprehensive - handles header, text, icon, and child items
- Polling handles FlowDrop's lazy sidebar loading

**Concerns**:
1. **Inline styles + CSS class is redundant** - The CSS file already defines `.flowdrop-category-agents` styles, but the JS also applies inline styles. Pick one.
2. **External Iconify URL** - `https://api.iconify.design/mdi:face-agent.svg` creates external dependency. Could fail offline or if Iconify changes.
3. **Polling every 2s forever** - Resource waste. Should stop after finding/styling the element, or use MutationObserver.

**Recommendation**:
- Remove inline style application, rely on CSS class only
- Bundle the SVG icon locally or use data URI
- Replace `setInterval` with `MutationObserver` that disconnects after success

---

#### 4. Auto-Attach Feature: `setupAutoAttach()` ✅ NEW FEATURE

**Implementation** (~100 lines):
- Tracks processed nodes in a Set
- Polls every 1s for new nodes
- When new tool detected (not agent, no incoming edges), finds nearest agent within 1000px
- Creates edge automatically with notification

**Verdict**: ⚠️ Clever UX but implementation concerns.

**Positives**:
- Great UX improvement - dragging a tool auto-connects it
- Uses nearest-agent logic (Euclidean distance)
- Shows success notification

**Concerns**:
1. **Polling every 1s** - Same issue as sidebar styling. Should use FlowDrop events if available.
2. **1000px threshold** - Magic number. Should be configurable or smarter (e.g., based on canvas zoom).
3. **No undo** - User can't easily undo auto-attachment without manually deleting edge.

**Recommendation**:
- Check if `window.FlowDrop.events` has `node:added` event to avoid polling
- Consider showing "Undo" in notification
- Make threshold relative to viewport/zoom

---

#### 5. Magnetic Drag Feature: `setupMagneticDrag()` ✅ NEW FEATURE

**Implementation** (~100 lines):
- Listens to `window.FlowDrop.events` for `node:dragstart` and `node:drag`
- When agent is dragged, recursively moves all connected children
- Uses `moveSubtree()` helper with cycle detection

**Verdict**: ✅ Well-implemented.

**Positives**:
- Uses proper FlowDrop event system (not polling!)
- Cycle detection prevents infinite loops
- Recursive subtree movement is correct

**Concerns**:
1. **Agent detection heuristic** is verbose - checks 5 different properties. Could be simplified.
2. **Fallback mutation** - Direct `targetNode.position = newPos` may not trigger React re-render properly.

**Recommendation**:
- Consolidate agent detection into a helper function
- Test the fallback path thoroughly - if `workflowActions.updateNodePosition` doesn't exist, the direct mutation may cause state sync issues

---

#### 6. RAG Index Tool: NEW PLUGIN (Untracked)

**Implementation**:
- `RagIndexTool.php` - FunctionCall plugin that searches Search API indexes
- `RagIndexToolDeriver.php` - Creates one tool per enabled Search API index

**Verdict**: ✅ Well-structured plugin.

**Positives**:
- Proper use of Drupal's deriver pattern
- Graceful fallback if search_api not installed
- Categorized as "Search"

**Concerns**:
1. **Not part of this issue** - This is RAG integration, separate from sidebar display fixes
2. **Error handling** - `setOutput()` for errors mixes error messages with results. Consider throwing or returning structured error.
3. **Chunk handling** - `getExtraData('content')` assumes AI Search module structure. May not work with vanilla Search API.

**Recommendation**:
- Move to separate issue/branch (this is feature creep)
- Add module dependency check for `search_api` in `.info.yml`
- Consider `search_api_ai` soft dependency

---

#### 7. Service Dependency: `FunctionGroupPluginManager` ✅ DONE

**Implementation**:
- Added to `flowdrop_ui_agents.services.yml`
- Injected into `AgentWorkflowMapper` constructor
- Used in `getToolCategory()` to fetch group labels dynamically

**Verdict**: ✅ Correct approach. This enables programmatic category labels from FunctionGroup metadata.

---

#### 8. Category System Improvements ✅ DONE

**Implementation**:
- `CATEGORY_PLURAL_MAP` expanded with proper labels (capitalized, human-readable)
- `getToolsByCategory()` now uses `uksort()` with weight-based sorting
- Agents get weight -20 (top), Chatbots get -5 (near top)

**Verdict**: ✅ Good improvement.

**Recommendation**: Remove the leading-space hack since `uksort` handles ordering.

---

### Code Quality Observations

| Aspect | Rating | Notes |
|--------|--------|-------|
| **Functionality** | ✅ Good | All planned features appear implemented |
| **Code Style** | ⚠️ Mixed | Some inconsistent brace placement (PHP), some verbose JS |
| **Performance** | ⚠️ Concerns | Multiple polling intervals (sidebar 2s, auto-attach 1s) |
| **Maintainability** | ⚠️ Mixed | Magic numbers, inline styles + CSS duplication |
| **Separation of Concerns** | ❌ Issue | RAG plugin shouldn't be in this branch |

---

### Recommended Next Steps

#### Immediate (before commit):

1. **Remove RAG plugin files** - Move to separate issue branch
2. **Remove leading space from category key** - Rely on `uksort` only
3. **Remove inline styles in `setupSidebarStyling()`** - Use CSS class only
4. **Test in browser** - Verify all three original issues are fixed

#### Future improvements:

1. Replace polling with MutationObserver/events where possible
2. Bundle icon SVG locally instead of external Iconify URL
3. Add "Undo" to auto-attach notification
4. Make auto-attach threshold zoom-aware

---

### Files Status Summary

| File | Original Issue | Status | Action Needed |
|------|----------------|--------|---------------|
| `NodesController.php` | Label fix | ✅ Done | Minor cleanup |
| `AgentWorkflowMapper.php` | Label + color | ✅ Done | Remove leading space |
| `flowdrop-agents-editor.js` | Styling + new features | ✅ Done | Remove inline styles |
| `flowdrop-agents-editor.css` | Color (teal) | ✅ Done | None |
| `services.yml` | Dependency | ✅ Done | None |
| `libraries.yml` | Version bump | ✅ Done | None |
| `RagIndexTool.php` | N/A (feature creep) | ⚠️ Untracked | Move to separate branch |
| `RagIndexToolDeriver.php` | N/A (feature creep) | ⚠️ Untracked | Move to separate branch |

---

## Data Flow Deep Dive (Reference Documentation)

This section explains how the sidebar node list is generated, what objects are in it, and how sorting works. Written to prevent future AI sessions from going in circles.

### 1. What Are The "Things" In The Sidebar?

The sidebar contains two types of items:

| Type | Source | Drupal Concept | Example |
|------|--------|----------------|---------|
| **Agents** | `ai_agents` config entities | Configuration Entity | `drupal_cms_assistant`, `evaluation_agent` |
| **Tools** | `FunctionCall` plugins | Plugin System | `tool:entity_list`, `ai_agent:vision` |

#### Agents (Config Entities)
- Stored in Drupal configuration (exportable YAML)
- Created via `/admin/config/ai/agents/add`
- Each agent has: `id`, `label`, `description`, `systemPrompt`, `tools[]`, `subAgents[]`
- Loaded via `\Drupal::entityTypeManager()->getStorage('ai_agent')->loadMultiple()`

#### Tools (Plugins)
- Defined via PHP class annotations/attributes
- Discovered by `FunctionCallPluginManager`
- Each tool has: `id`, `name`, `description`, `category` (from FunctionGroup)
- Examples: `@FunctionCall(id = "entity_list", ...)` or `#[FunctionCall(...)]`

### 2. Data Flow: PHP → API → Frontend

```
┌─────────────────────────────────────────────────────────────────┐
│ STEP 1: Data Sources (PHP)                                      │
├─────────────────────────────────────────────────────────────────┤
│ Agents:                                                          │
│   EntityTypeManager::getStorage('ai_agent')->loadMultiple()     │
│   → Returns AiAgent config entities                              │
│                                                                  │
│ Tools:                                                           │
│   FunctionCallPluginManager::getDefinitions()                   │
│   → Returns tool plugin definitions with category from          │
│     FunctionGroupPluginManager                                   │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ STEP 2: Transform to Node Format (AgentWorkflowMapper.php)     │
├─────────────────────────────────────────────────────────────────┤
│ getAvailableAgents($owner):                                      │
│   - Loops through AiAgent entities                               │
│   - Returns array with: id, name, type='agent', category,       │
│     icon, color, inputs[], outputs[], configSchema              │
│                                                                  │
│ getAvailableTools($owner):                                       │
│   - Loops through FunctionCall plugins                           │
│   - Calls getToolCategory() to get label from FunctionGroup     │
│   - Returns array with: id, name, type='tool', category, etc.   │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ STEP 3: Group by Category (NodesController.php)                 │
├─────────────────────────────────────────────────────────────────┤
│ getNodesByCategory():                                            │
│   1. Get tools grouped by category via getToolsByCategory()     │
│   2. Add agents under 'Sub-Agent Tools' key                      │
│   3. Sort categories with uksort() using weights:               │
│      - 'sub-agent' or 'agent' → weight -20 (first)              │
│      - 'chatbot' → weight -5                                     │
│      - everything else → weight 0 (alphabetical)                │
│   4. Return JSON: { data: { "Category": [...nodes] }, ... }    │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ STEP 4: API Response (/api/flowdrop-agents/nodes/by-category)  │
├─────────────────────────────────────────────────────────────────┤
│ {                                                                │
│   "success": true,                                               │
│   "data": {                                                      │
│     "Sub-Agent Tools": [ { agent nodes... } ],     ← FIRST     │
│     "Drupal Core Actions": [ { tool nodes... } ],               │
│     "Entity Tools": [ ... ],                                     │
│     ...                                                          │
│   },                                                             │
│   "categories": ["Sub-Agent Tools", "Drupal Core Actions", ...] │
│ }                                                                │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ STEP 5: Frontend Receives & RE-SORTS (FlowDrop UI Core)        │
├─────────────────────────────────────────────────────────────────┤
│ flowdrop.es.js line 23214:                                       │
│   return Array.from(categories2).sort();  ← ALPHABETICAL!       │
│                                                                  │
│ This DESTROYS our PHP sorting because:                           │
│   - "D"rupal Core Actions < "S"ub-Agent Tools alphabetically    │
│   - Result: Sub-Agent Tools is NOT first in sidebar             │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ STEP 6: Sidebar Renders Categories Alphabetically               │
├─────────────────────────────────────────────────────────────────┤
│ Final order (alphabetical):                                      │
│   1. Drupal Core Actions                                         │
│   2. Entity Tools                                                │
│   3. Information Tools                                           │
│   4. Modification Tools                                          │
│   5. Other                                                       │
│   6. Sub-Agent Tools    ← Should be FIRST, but is 6th!          │
│   7. Tools API                                                   │
└─────────────────────────────────────────────────────────────────┘
```

### 3. Why The Leading Space Hack Worked

Previously, the category was named `" Sub-Agent Tools"` (with a leading space).

ASCII values:
- Space character: `32`
- Letter "D": `68`

Since `32 < 68`, `" Sub-Agent Tools"` sorted before `"Drupal Core Actions"`.

**This was removed** during cleanup because it seemed like a hack, but it was actually compensating for the frontend's alphabetical re-sort.

### 4. Node Object Structure

Each node in the API response has this structure:

```javascript
{
  // Identity
  "id": "ai_agent_evaluation_agent",        // Unique node ID for the UI
  "name": "Evaluation Agent",               // Display name
  "type": "agent",                          // Either "agent" or "tool"
  
  // Categorization
  "category": "Sub-Agent Tools",            // Which sidebar group
  "tags": ["agent"],                        // Additional metadata
  
  // Visual
  "icon": "mdi:face-agent",                 // MDI icon name
  "color": "var(--color-ref-green-500)",    // CSS variable or hex color
  
  // Plugin References
  "executor_plugin": "ai_agents::ai_agent::evaluation_agent",  // How to execute
  "agent_id": "evaluation_agent",           // Config entity ID (agents only)
  "tool_id": "...",                         // Plugin ID (tools use this)
  
  // Ports (for connecting nodes)
  "inputs": [
    { "id": "trigger", "name": "Trigger", "dataType": "trigger", ... },
    { "id": "message", "name": "Message", "dataType": "string", ... }
  ],
  "outputs": [
    { "id": "response", "name": "Response", "dataType": "string", ... }
  ],
  
  // Configuration Panel
  "configSchema": { ... }                   // JSON Schema for node settings
}
```

### 5. How Categories Are Determined

#### For Agents (hardcoded):
```php
// AgentWorkflowMapper.php:884
'category' => 'Sub-Agent Tools',  // All agents use this
```

#### For Tools (dynamic from FunctionGroup):
```php
// AgentWorkflowMapper.php:getToolCategory()
$definition = $this->functionCallPluginManager->getDefinition($toolId);
$groupId = $definition['category'] ?? $definition['group'] ?? NULL;
if ($groupId && $this->functionGroupPluginManager->hasDefinition($groupId)) {
    $groupDef = $this->functionGroupPluginManager->getDefinition($groupId);
    return (string) ($groupDef['group_name'] ?? 'Other');
}
```

#### FunctionGroup Example (from ai_agents module):
```php
#[FunctionGroup(
  id: 'agent_tools',
  group_name: new TranslatableMarkup('Sub-Agent Tools'),
  weight: -10,  // Not currently used for sorting
)]
```

### 6. Solution Options

| Option | Approach | Pros | Cons |
|--------|----------|------|------|
| **A: Leading Space** | Use `" Sub-Agent Tools"` | Works immediately | Hacky, can break if trimmed |
| **B: Prefix Number** | Use `"1. Sub-Agent Tools"` | Clear intent, sorts correctly | Ugly display, need to strip in UI |
| **C: Fix FlowDrop Core** | PR to FlowDrop to preserve order | Proper fix | Requires upstream change |
| **D: Category Weight API** | Add `weight` field to node response, have FlowDrop use it | Clean, flexible | Requires both PHP + JS changes |

#### IMPLEMENTED: Option D - Category Type Weight System

**Status**: ✅ Implemented on 2025-01-03

We implemented a robust category type weight system that:
1. Groups categories into "category types" (agents, chatbots, search, mcp, tools, other)
2. Each type has a weight that controls sort order
3. Categories within the same type sort alphabetically
4. Each node includes `categoryWeight` for frontend sorting
5. API includes `categoryMeta` with weight/type info

**Architecture**:
```
Category Types (ordered by weight):
├── agents (-100)     → Sub-Agent Tools, Assistants, Orchestrators
├── chatbots (-90)    → DeepChat, Chatbots (future)
├── search (-80)      → RAG Indexes, Search API (future)
├── mcp (-70)         → MCP Tools (future)
├── tools (0)         → Entity Tools, Information Tools, etc.
└── other (100)       → Uncategorized (catch-all)
```

**Files Modified**:

1. **NodesController.php** - Added category type system:
   - `CATEGORY_TYPE_WEIGHTS` constant with type weights
   - `CATEGORY_TYPE_MAP` constant for explicit mappings
   - `getCategoryType()` method with fallback keyword matching
   - `getCategoryWeight()` method
   - Added `categoryWeight` to each node
   - Added `categoryMeta` to API response

2. **FlowDrop Patch** - `patches/drupal/flowdrop/category-weight-sorting.patch`:
   - Modifies **`flowdrop.iife.js`** (NOT `.es.js`) - see CRITICAL note below
   - Reads `categoryWeight` from each node
   - Uses IIFE wrapper for `const` declarations in expression context

3. **patches.json** - Registered the FlowDrop patch

**CRITICAL: ES vs IIFE Builds**
```
flowdrop.es.js   → ES Modules format (import/export) - used by bundlers
flowdrop.iife.js → IIFE format (browser-compatible) - WHAT DRUPAL LOADS
```
Drupal's library system uses `<script>` tags, not ES modules. Always patch the IIFE build!

**API Response Structure**:
```json
{
  "data": {
    "Sub-Agent Tools": [
      { "name": "Agent 1", "categoryWeight": -100, ... }
    ],
    "Entity Tools": [
      { "name": "Tool 1", "categoryWeight": 0, ... }
    ]
  },
  "categoryMeta": {
    "Sub-Agent Tools": { "weight": -100, "type": "agents" },
    "Entity Tools": { "weight": 0, "type": "tools" }
  }
}
```

**To Apply the Patch** (manual until upstream accepts):
```bash
cd web/modules/contrib/flowdrop
patch -p1 < ../../../../patches/drupal/flowdrop/category-weight-sorting.patch
```

**Future**: Submit patch as MR to FlowDrop module for upstream inclusion

### 7. Where Each File Fits

| File | Role | Key Functions |
|------|------|---------------|
| `NodesController.php` | API endpoint | `getNodes()`, `getNodesByCategory()` - adds categoryWeight |
| `AgentWorkflowMapper.php` | Data transformer | `getAvailableAgents()`, `getAvailableTools()`, `getToolCategory()` |
| `flowdrop.iife.js` | Frontend (FlowDrop core) | **IIFE build - what Drupal loads**. Category sorting patched here |
| `flowdrop.es.js` | ES Module build | NOT used by Drupal - for bundler-based projects only |
| `flowdrop-agents-editor.js` | Our JS customizations | `setupSidebarStyling()` - applies teal color to agent category |
| `flowdrop-agents-editor.css` | Our CSS | `.flowdrop-category-agents` styles |

**Important**: The sidebar calls `/api/flowdrop-agents/nodes` (flat list), NOT `/nodes/by-category`. Both endpoints must include `categoryWeight` on each node.

---

## Category Order Bug: FIXED ✅

**Status**: ✅ Fixed on 2025-01-03 using Category Type Weight System (Option D)

**Root Cause**: FlowDrop UI core re-sorted categories alphabetically at `flowdrop.es.js:23214`

**Solution Implemented**:
1. Added category type weight system to `NodesController.php`
2. Each node now includes `categoryWeight` field
3. Created patch for FlowDrop UI to sort by weight instead of alphabetically
4. Patch registered in `patches.json`

**Verification** (tested via drush):
```
Categories: Sub-Agent Tools, Drupal Core Actions, Entity Tools, Information Tools, Modification Tools, Tools API, Other
categoryMeta: {
    "Sub-Agent Tools": { "weight": -100, "type": "agents" },
    "Drupal Core Actions": { "weight": 0, "type": "tools" },
    ...
    "Other": { "weight": 100, "type": "other" }
}
First agent categoryWeight: -100
```

**To fully activate**: Apply the FlowDrop patch manually:
```bash
cd web/modules/contrib/flowdrop
patch -p1 < ../../../../patches/drupal/flowdrop/category-weight-sorting.patch
```
