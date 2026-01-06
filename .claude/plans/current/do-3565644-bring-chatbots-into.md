# Bring Chatbots into FlowDrop UI

- **Issue**: [#3565644](https://www.drupal.org/project/flowdrop_ui_agents/issues/3565644)
- **Branch**: `3565644-bring-chatbots-into`
- **Status**: Planning

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
- [ ] Create chatbot node type in `AgentWorkflowMapper`
- [ ] Define ports: output `trigger` to connect to assistant's trigger input
- [ ] Define config schema for chatbot settings (see below)
- [ ] Add `getAvailableChatbots()` method to discover existing blocks
- [ ] **Filter to only unassigned chatbots** (no `ai_assistant` set, or assistant doesn't exist)
- [ ] Add chatbot nodes to `NodesController::getNodes()`
- [ ] Register "Chatbots" category in sidebar (weight -90, below Agents)

### Phase 3: Save Logic
- [ ] Add chatbot handling to `AssistantSaveController`
- [ ] Save chatbots AFTER assistant (they need assistant ID)
- [ ] Create/update block.block.* config entities
- [ ] Map FlowDrop node config → block config structure
- [ ] Handle theme/region config with sensible defaults

### Phase 4: Load Logic (Existing Workflows)
- [ ] Detect chatbots linked to current assistant
- [ ] Create chatbot nodes from block.block.* entities
- [ ] Position chatbot nodes left of assistant (x offset)
- [ ] Create edges from chatbot trigger → assistant trigger

### Phase 5: UI Polish
- [ ] Chatbot node styling (icon: `mdi:chat`, color: trigger red `#ef4444`)
- [ ] Validation: warn if chatbot not connected
- [ ] Error messaging for save failures

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

## Reference Files

| File | Purpose |
|------|---------|
| `web/modules/contrib/ai/modules/ai_chatbot/src/Plugin/Block/DeepChatFormBlock.php` | Block config form |
| `web/modules/contrib/ai/modules/ai_chatbot/src/Controller/DeepChatApi.php` | API endpoint |
| `recipes/bundle_lister_demo/config/block.block.bundle_lister_chatbot.yml` | Example config |
| `modules/flowdrop_ui_agents/src/Controller/Api/NodesController.php` | Add chatbot discovery |
| `modules/flowdrop_ui_agents/src/Service/AgentWorkflowMapper.php` | Add chatbot node type |
| `modules/flowdrop_ui_agents/src/Controller/Api/AssistantSaveController.php` | Add chatbot save logic |
