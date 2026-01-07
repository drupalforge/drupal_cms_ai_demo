# Bring Chatbots into FlowDrop UI

- **Issue**: [#3565644](https://www.drupal.org/project/flowdrop_ui_agents/issues/3565644)
- **Branch**: `3565644-bring-chatbots-into`
- **Status**: Complete

## Goal

Add DeepChat chatbot nodes to the FlowDrop UI, allowing users to visually connect chatbots to assistants. Chatbots act as triggers that initiate conversations with assistants.

## Requirements (from issue)

1. Chatbots are triggers - should use trigger type
2. Appear below Agents in sidebar (new "Chatbots" category)
3. Use DeepChat block for configuration
4. Saving chatbot node saves to chatbot config entity (not flowdrop)
5. Position left of assistants on canvas
6. Assistant can have multiple chatbots (but usually one)
7. Assistants must be created/saved before chatbots can reference them
8. Bring across all config including theme/region (needs UI even if chatbot doesn't really use regions)
9. Use DeepChat (ai_chatbot module), NOT deprecated "chatbot"
10. Reference: Drupal CMS Assistant has a chatbot attached
11. **Sidebar filtering**: Only show chatbots that DON'T already have an assistant assigned (unassigned chatbots only)
12. Chatbots can only connect to 1 assistant (1:1 relationship from chatbot side)

## Architecture Understanding

### Current Node Types
| Type | Color | Ports | Purpose |
|------|-------|-------|---------|
| `agent` | Purple | trigger(in), message(in), response(out), tools(out) | AI Agent |
| `assistant` | Teal | Same as agent + LLM config | Wraps agent with LLM settings |
| `tool` | Orange | tool(in), tool(out) | Function call |

### New Chatbot Node Type
| Type | Color | Ports | Purpose |
|------|-------|-------|---------|
| `chatbot` | Red? | trigger(out) | DeepChat block that triggers assistant |

### Current Category Weights
```php
'agents' => -100,    // Top
'chatbots' => -90,   // Below agents (already defined!)
'tools' => 0,        // Bottom
```

### Save Process
1. Topological sort (leaves first)
2. Save sub-agents first
3. Save main assistant
4. **NEW**: Save chatbots last (they reference assistant ID)

### Key Files
- `NodesController.php` - Sidebar API, node discovery
- `AgentWorkflowMapper.php` - Entity ↔ FlowDrop conversion
- `AssistantSaveController.php` - Multi-entity save logic

## Tasks

### Phase 1: Research & Discovery
- [x] Understand DeepChat block config structure
- [x] Find how chatbots link to assistants (config key: `ai_assistant`)
- [x] Identify all chatbot config fields that need UI
- [ ] Check existing chatbot block configs in demo site

### Phase 2: Node Definition
- [x] Create chatbot node type in `AgentWorkflowMapper` (for workflow load/save)
- [x] Define ports: output `trigger` to connect to assistant's trigger input
- [x] Define config schema for chatbot settings (`getChatbotConfigSchema()`)
- [x] Add `getAvailableChatbots()` method to discover existing blocks
- [x] **Filter to only unassigned chatbots** (no `ai_assistant` set)
- [x] Add chatbot nodes to `NodesController::getNodes()`
- [x] Add chatbot nodes to `NodesController::getNodesByCategory()`
- [x] Register "Chatbots" category color in `AgentWorkflowMapper`

### Phase 3: Save Logic
- [x] Add chatbot handling to `AssistantSaveController`
- [x] Save chatbots AFTER assistant (they need assistant ID)
- [x] Create/update block.block.* config entities
- [x] Map FlowDrop node config → block config structure
- [x] Handle theme/region config with sensible defaults

### Phase 4: Load Logic (Existing Workflows)
- [x] Detect chatbots linked to current assistant (`addLinkedChatbots()`)
- [x] Create chatbot nodes from block.block.* entities
- [x] Position chatbot nodes left of assistant (x offset -350)
- [x] Create edges from chatbot trigger → assistant trigger

### Phase 5: UI Polish
- [x] Chatbot node styling (purple theme with gradient background)
- [x] "CHAT" badge matching tool node "TOOL" badge style
- [x] Config panel with collapsible sections (Placement, Messages, Styling, Advanced, Visibility)
- [x] Visibility settings with custom UI (Pages, Response Status, Roles, Content Types, Vocabulary)
- [x] Dynamic region dropdown based on selected theme
- [x] Toggle switches for boolean fields
- [x] Fix select dropdown labels (Theme, Style, Placement, Toggle State)
- [x] Validation: warn if chatbot not connected
- [x] Error messaging for save failures

## Technical Notes

### DeepChat Block Config Structure

**Plugin ID**: `ai_deepchat_block`

**Key Configuration Fields** (from `DeepChatFormBlock::blockForm()`):
```php
// Required - links to assistant
$form['ai_assistant'] = [
  '#type' => 'select',
  '#title' => 'AI Assistant',
  '#options' => $assistants,  // List of ai_assistant entities
  '#required' => TRUE,
];

// Display settings
$form['messages']['bot_name'] = ['#type' => 'textfield'];
$form['messages']['initial_message'] = ['#type' => 'textarea'];

// Styling
$form['styling']['placement'] = [
  '#type' => 'select',
  '#options' => [
    'toolbar' => 'Toolbar',
    'bottom-right' => 'Bottom right',
    'bottom-left' => 'Bottom left',
  ],
];
$form['styling']['style_file'] = ['#type' => 'select'];  // Theme presets
```

### Chatbot → Assistant Relationship

**Config key**: `settings.ai_assistant` in block config

Example from `block.block.bundle_lister_chatbot.yml`:
```yaml
id: bundle_lister_chatbot
plugin: ai_deepchat_block
settings:
  ai_assistant: bundle_lister_assistant  # <-- Links to assistant ID
  bot_name: 'Bundle Lister'
  placement: bottom-right
  style_file: 'module:ai_chatbot:bard.yml'
region: footer_bottom
theme: drupal_cms_olivero
```

### Block Entity Structure

Chatbots are stored as `block.block.*` config entities:
- **Entity type**: `block`
- **Plugin**: `ai_deepchat_block`
- **Storage**: `config/sync/block.block.{id}.yml`

To create programmatically:
```php
$block = Block::create([
  'id' => 'my_chatbot',
  'plugin' => 'ai_deepchat_block',
  'region' => 'footer_bottom',
  'theme' => 'drupal_cms_olivero',
  'settings' => [
    'ai_assistant' => 'my_assistant',
    'bot_name' => 'My Bot',
    'placement' => 'bottom-right',
  ],
]);
$block->save();
```

### Save Order

```
1. Sub-agents (leaves first, via topological sort)
2. Main assistant entity
3. Backing AI agent entity  
4. Chatbot blocks (need assistant ID to exist)
```

### Theme/Region Handling

**Problem**: Blocks require theme + region, but FlowDrop is theme-agnostic.

**Solution Options**:
1. **Default values**: Use `drupal_cms_olivero` + `footer_bottom` as defaults
2. **Config schema**: Add theme/region to chatbot node config panel
3. **Smart detection**: Use the active theme when saving

**Recommended**: Option 2 - Add to config panel with sensible defaults. Users can change if needed.

### Discovering Existing Chatbots

To find chatbots linked to an assistant:
```php
$blocks = \Drupal::entityTypeManager()
  ->getStorage('block')
  ->loadByProperties([
    'plugin' => 'ai_deepchat_block',
  ]);

foreach ($blocks as $block) {
  $settings = $block->get('settings');
  if ($settings['ai_assistant'] === $assistantId) {
    // This chatbot is linked to our assistant
  }
}
```

## FlowDrop Node Schema

### Chatbot Node Data Structure
```javascript
{
  id: 'chatbot-1',
  type: 'universalNode',
  position: { x: -200, y: 100 },  // Left of assistant
  data: {
    nodeId: 'chatbot-1',
    nodeType: 'chatbot',
    label: 'My Chatbot',
    config: {
      blockId: 'my_chatbot',           // Existing block ID (null for new)
      botName: 'Assistant Bot',
      initialMessage: 'Hello!',
      placement: 'bottom-right',
      styleFile: 'module:ai_chatbot:bard.yml',
      theme: 'drupal_cms_olivero',
      region: 'footer_bottom',
    },
    metadata: {
      id: 'chatbot:my_chatbot',
      type: 'chatbot',
      icon: 'mdi:chat',
      color: '#ef4444',
      outputs: [
        { id: 'trigger', name: 'Trigger', dataType: 'trigger' }
      ],
      inputs: [],
      configSchema: { /* ... */ }
    }
  }
}
```

### Edge: Chatbot → Assistant
```javascript
{
  id: 'edge-chatbot-assistant',
  source: 'chatbot-1',
  sourceHandle: 'chatbot-1-output-trigger',
  target: 'assistant-node',
  targetHandle: 'assistant-node-input-trigger',
}
```

## Blockers / Questions

1. ~~**Q**: What config fields does DeepChat block have?~~ **A**: See above
2. ~~**Q**: How is the assistant referenced in chatbot config?~~ **A**: `settings.ai_assistant`
3. **Q**: Can we create blocks without placing them in a visible region? May need a "hidden" region or disabled status.
4. **Q**: Should we support multiple chatbots per assistant in the UI? (Yes per requirements, but need UX thought)
5. **Q**: How to handle block ID generation for new chatbots?

## Implementation Notes

### Completed Work (Jan 5, 2026)

#### Chatbot Node Discovery & Canvas Loading
- `getAvailableChatbots()` in `AgentWorkflowMapper.php` - filters to show only unassigned chatbots
- `NodesController.php` updated to include chatbots in sidebar with category weight -90
- `addLinkedChatbots()` in `AssistantEditorController.php` - loads chatbots linked to an assistant onto canvas

#### Chatbot Config Panel
- Comprehensive config schema with all DeepChat block fields
- Collapsible group sections: Block Placement, Message Settings (expanded), Styling, Advanced, Visibility
- Toggle switches for boolean fields
- `fixSelectLabels()` - FlowDrop doesn't use `enumNames` so we post-process selects

#### Visibility Settings (Custom UI)
Implemented as collapsible subgroups within Visibility section:
- **Pages**: Textarea + radio buttons (Show/Hide for listed pages)
- **Response Status**: Checkboxes for 200, 403, 404
- **Roles**: Checkboxes dynamically loaded from Drupal + Negate toggle
- **Content Types**: Checkboxes dynamically loaded + Negate toggle
- **Vocabulary**: Checkboxes dynamically loaded + Negate toggle

Each subgroup header shows "(Not restricted)" or "(Restricted)" status.

#### Chatbot Node Styling
- Purple gradient background (`#f3e8ff` to `#ede9fe`) via CSS
- Purple border (`#a855f7`) matching tool node style
- "CHAT" badge in top-right corner (matches "TOOL" badge on tool nodes)
- `setupChatbotNodeStyling()` function polls for chatbot nodes and adds styling

#### Panel Width/Scroll Fixes
- CSS class `flowdrop-chatbot-panel-fix` applied to parent panel
- Fixed horizontal scrollbar issues with proper box-sizing and max-width
- Panel width set to 400px

### Files Modified
| File | Changes |
|------|---------|
| `js/flowdrop-agents-editor.js` | Config panel, visibility UI, node styling, validation, save feedback |
| `css/flowdrop-agents-editor.css` | Chatbot node styling with svelte-flow selectors |
| `src/Controller/AssistantEditorController.php` | Chatbot loading, config schema, visibility extraction |
| `src/Controller/Api/NodesController.php` | Chatbot category in sidebar |
| `src/Controller/Api/AssistantSaveController.php` | Chatbot save logic with block entity creation |
| `src/Service/AgentWorkflowMapper.php` | `getAvailableChatbots()`, chatbot config schema |

### Remaining Work
All features implemented and tested:
1. ~~**Save Logic**: Implement chatbot saving in `AssistantSaveController`~~ ✓
2. ~~**Validation**: Warn if chatbot not connected to assistant~~ ✓
3. ~~**Error Handling**: Save failure messaging~~ ✓
4. ~~**Testing**: Visual verification of chatbot node styling~~ ✓

### Session 2026-01-06

**Final implementation commit**: `310c6c7`

**Key Fix**: FlowDrop uses **Svelte Flow**, not React Flow. All CSS selectors were targeting `.react-flow__node` but should use `.svelte-flow__node`. This was the root cause of the chatbot node styling not appearing.

**Verified**:
- Purple gradient background renders on chatbot nodes ✓
- "CHAT" badge appears in top-right corner ✓
- Chatbot nodes are visually distinct from agents (green) and tools (orange) ✓
- Save functionality works - shows "1 chatbot(s) saved" notification ✓

**Screenshots**:
- `.claude/screenshots/chatbot-node-styling.png` - Shows chatbot node with purple styling
- `.claude/screenshots/chatbot-save-success.png` - Shows save success notification

### Session 2026-01-07

**Chatbot Node Styling Overhaul**

Completely redesigned chatbot nodes to match tool node visual style:

**Changes**:
- Fixed width at 288px (matches tool nodes)
- Light purple background (#f3e8ff) with purple border (#a855f7)
- Compact card layout with proper header, icon, and title styling
- Connection handle (purple dot) centered vertically on right edge
- "CHAT" badge positioned top-right corner (-8px, -5px)
- Config cog with hover fade effect (z-index: 200)
- Ports container hidden but handles remain visible
- Custom body text: "Chat Message Input on Gin theme in Content Region."

**Technical approach**:
- FlowDrop uses Svelte Flow which aggressively overwrites CSS
- JS enforcement via `setProperty('important')` required for handles
- Badge injection uses container fallback chain: `.universal-node` → `.flowdrop-workflow-node.parentNode` → `:scope > div`
- 500ms interval polls for dynamically added chatbot nodes

**Commits**:
- `d1f152c` - Overhaul chatbot node styling to match tool node design
- `c45bf80` - Add z-index to config button and show on hover

## Known Issues

### Config Cog / CHAT Badge Overlap
The config cog (gear icon) appears behind the CHAT badge when both are in the top-right corner. The badge has z-index: 101, config button has z-index: 200, but they're in different stacking contexts.

**Status**: Will be addressed in a separate follow-up issue.

**Potential solutions**:
1. Move badge to different position (top-left)
2. Make badge semi-transparent on hover
3. Add `pointer-events: none` to badge on hover
4. Move config cog to different position (bottom-right)

## Code Review Notes

### CSS (`css/flowdrop-agents-editor.css`)
- Clean structure with proper comments explaining approach
- Uses `!important` where necessary (Svelte Flow overrides styles)
- BEM naming convention followed for custom classes
- Some comments explain "why" not just "what" (e.g., position: static critical note)

### JS (`js/flowdrop-agents-editor.js`)
- Large file (~2200 lines) - consider splitting in future
- Mixes `function()` and arrow function syntax (minor inconsistency)
- Good separation of concerns (setupChatbotContent, enhanceConfigPanel, etc.)
- Interval polling approach necessary due to Svelte Flow's dynamic rendering
- Template strings contain CSS which duplicates external CSS file (unavoidable for dynamic injection)

### PHP (`src/Service/AgentWorkflowMapper.php`)
- PSR-12 brace placement inconsistency (some methods have `{` on new line, others same line)
- Changed chatbot color from red to purple (`var(--color-ref-purple-500)`)
- Clean PHPDoc comments with proper type hints
- Code formatting changes (else/elseif on same line as closing brace)

## Reference Files

| File | Purpose |
|------|---------|
| `web/modules/contrib/ai/modules/ai_chatbot/src/Plugin/Block/DeepChatFormBlock.php` | Block config form |
| `web/modules/contrib/ai/modules/ai_chatbot/src/Controller/DeepChatApi.php` | API endpoint |
| `recipes/bundle_lister_demo/config/block.block.bundle_lister_chatbot.yml` | Example config |
| `modules/flowdrop_ui_agents/src/Controller/Api/NodesController.php` | Add chatbot discovery |
| `modules/flowdrop_ui_agents/src/Service/AgentWorkflowMapper.php` | Add chatbot node type |
| `modules/flowdrop_ui_agents/src/Controller/Api/AssistantSaveController.php` | Add chatbot save logic |
