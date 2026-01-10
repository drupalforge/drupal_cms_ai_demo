# Unified Modeler API Entry Points

- **Issue**: [#3566771](https://www.drupal.org/project/flowdrop_ui_agents/issues/3566771)
- **Branch**: `3566771-unified-modeller-api`
- **Status**: Future (refactor/architecture exploration)

## Current Focus (Revised)

We will **stabilize save behavior and tests** first, with minimal changes.
A broader refactor around Modeler API ownership/unified save semantics will be
tracked in a **new issue** later.

### Immediate Goals

- Make saves reliable for existing Agent/Assistant/Chatbot entry points.
- Align tests with the current save flow and fix remaining failures.
- Keep changes minimal; avoid large architectural shifts until a new issue.

## Problem

The `AssistantSaveController` bypasses the Modeler API entirely. Instead of using the proper `prepareModelFromData()` flow, it directly manipulates entities.

### Current Architecture (Incorrect)

```
FlowDrop UI (Assistant View)
    │
    ▼
AssistantSaveController::save()
    ├── Direct $agent->set('tools', [...])
    ├── Direct $agent->save()
    ├── Direct $assistant->save()
    └── Direct Block::save() for chatbots
```

### Target Architecture (Modeler API)

```
FlowDrop UI (Assistant View)
    │
    ▼
ModelerApi::save()
    └── Api::prepareModelFromData()
        ├── modeler.parseData()              → Parse workflow JSON
        ├── modeler.readComponents()         → Create Component objects
        ├── owner.resetComponents()          → Clear existing config
        ├── owner.addComponent() × N         → Process each component
        ├── owner.finalizeAddingComponents() → Save related entities
        └── model.save()                     → Save primary entity
```

## Two FlowDrop Entry Points

| Entry Point | Route | ModelOwner | Saves |
|-------------|-------|------------|-------|
| **Agents page** | `/admin/config/ai/agents/{id}/edit_with/flowdrop_agents` | `ai_agents_agent` (existing) | Agent + tools + sub-agents |
| **Assistants page** | `/admin/config/ai/ai-assistant/{id}/edit-flowdrop` | `flowdrop_ui_agents_assistant` (NEW) | Assistant + linked agent + tools + sub-agents + chatbots |

## Architecture Decision

**Single `Assistant` ModelOwner** that handles the entire save flow from the Assistant FlowDrop view:

```
Assistant ModelOwner (configEntityTypeId: ai_assistant)
│
├── TYPE_START → Assistant config (LLM, history, label)
│               + Linked Agent config (system_prompt, max_loops, description)
│
├── TYPE_ELEMENT → Tools (saved to linked agent's tools array)
│
├── TYPE_SUBPROCESS → Sub-agents (tracked, saved in finalize)
│
├── TYPE_TRIGGER → Chatbots (tracked, saved in finalize)
│
└── TYPE_LINK → Edges (no entity save needed)

finalizeAddingComponents():
  1. Save sub-agents (leaf first, topological order)
  2. Save main linked agent (with tools array)
  3. Assistant saves automatically via Modeler API
  4. Save chatbot blocks (reference assistant ID)
```

## Entity Relationships

```
┌──────────────────────────────────────────────────────────────────┐
│                     FLOWDROP ASSISTANT VIEW                       │
├──────────────────────────────────────────────────────────────────┤
│                                                                   │
│  ┌──────────┐         ┌───────────────┐         ┌──────────────┐ │
│  │ Chatbot  │────────▶│   Assistant   │────────▶│ Linked Agent │ │
│  │ (block)  │references│(ai_assistant) │ links to│  (ai_agent)  │ │
│  └──────────┘         └───────────────┘         └──────┬───────┘ │
│                                                        │         │
│                              ┌─────────────────────────┴───────┐ │
│                              ▼                                 ▼ │
│                        ┌───────────┐                    ┌───────┐│
│                        │ Sub-Agent │                    │ Tools ││
│                        │(ai_agent) │                    │(config)│
│                        └───────────┘                    └───────┘│
└──────────────────────────────────────────────────────────────────┘
```

## Key Implementation Details

### Component Type Mapping

| FlowDrop Node Type | Component Type | Entity | Handled By |
|--------------------|----------------|--------|------------|
| `assistant` | `TYPE_START` (1) | `ai_assistant` + `ai_agent` | `handleStartComponent()` |
| `tool` | `TYPE_ELEMENT` (4) | Agent's `tools` array | `handleElementComponent()` |
| `agent` / `agent-collapsed` | `TYPE_SUBPROCESS` (2) | `ai_agent` | `handleSubprocessComponent()` |
| `chatbot` | `TYPE_ANNOTATION` (7) | `block` | `handleChatbotComponent()` |
| edges | `TYPE_LINK` (5) | (none) | Skip |

**Note**: Chatbots use `ANNOTATION` type because it's semantically close (auxiliary/metadata elements) and we don't use annotations for other purposes.

### Save Order (Critical)

1. **Sub-agents** (leaf-first topological sort) - so parents can reference them
2. **Main linked agent** (with tools array populated)
3. **Assistant** (automatically by Modeler API after `finalizeAddingComponents()`)
4. **Chatbots** (reference assistant ID, must exist first)

### Reuse Agent Component Logic

Extract shared logic from `ai_agents_agent` ModelOwner into a trait or service:
- Tool array building
- Tool usage limits handling
- Sub-agent reference building

## Tasks

### Phase 1: Create Assistant ModelOwner
- [ ] Create `src/Plugin/ModelerApiModelOwner/Assistant.php`
- [ ] Implement `configEntityTypeId()` → `ai_assistant`
- [ ] Implement `configEntityProviderId()` → `ai_assistant_api`
- [ ] Implement `supportedOwnerComponentTypes()`
- [ ] Implement `modelIdExistsCallback()`

### Phase 2: Implement Component Handling
- [ ] Implement `resetComponents()` - clear assistant + linked agent config
- [ ] Implement `addComponent()` for TYPE_START (assistant + agent fields)
- [ ] Implement `addComponent()` for TYPE_ELEMENT (tools → agent)
- [ ] Implement `addComponent()` for TYPE_SUBPROCESS (track sub-agents)
- [ ] Implement `addComponent()` for TYPE_TRIGGER (track chatbots)
- [ ] Implement `finalizeAddingComponents()` with correct save order

### Phase 3: Implement Read Flow (usedComponents)
- [ ] Implement `usedComponents()` to extract current config
- [ ] Return assistant config as TYPE_START component
- [ ] Return tools as TYPE_ELEMENT components
- [ ] Return sub-agents as TYPE_SUBPROCESS components
- [ ] Return chatbots as TYPE_TRIGGER components

### Phase 4: Update WorkflowParser
- [ ] Update `toComponents()` to create correct component types
- [ ] Ensure chatbot nodes create TYPE_TRIGGER components
- [ ] Ensure all node config is properly mapped to component config

### Phase 5: Update FlowDropAgents Modeler
- [ ] Add Assistant ModelOwner support
- [ ] Update `parseData()` to work with assistant workflows
- [ ] Ensure `readComponents()` returns all component types

### Phase 6: Migrate Save Route
- [ ] Update JS to call Modeler API save endpoint (or keep custom route that delegates)
- [ ] Update `AssistantSaveController` to delegate to Modeler API
- [ ] OR remove custom controller and use standard Modeler API route

### Phase 7: Tests
- [ ] Create `AssistantModelOwnerTest` (Kernel test)
  - [ ] Test saving new assistant with tools
  - [ ] Test saving assistant with sub-agents
  - [ ] Test saving assistant with chatbots
  - [ ] Test save order (sub-agents → agent → assistant → chatbots)
  - [ ] Test round-trip (save → load → save → verify no data loss)
- [ ] Create `AssistantSaveIntegrationTest` (Functional test)
  - [ ] Test save via Modeler API endpoint
  - [ ] Test chatbot visibility settings preserved
  - [ ] Test tool usage limits preserved

## Technical Notes

### How AiAssistantForm Saves (Reference)

From `AiAssistantForm::submitForm()`:
```php
// For NEW assistants (lines 568-582):
$agent = $storage->create([...]);
$agent->save();
$entity->set('ai_agent', $agent->id());

// For EXISTING assistants (lines 584-600):
$agent = $storage->load($ai_agent_id);
$agent->set('tools', $tools);
$agent->set('description', ...);
$agent->save();

// Then parent::save() saves the assistant
```

The form explicitly saves both entities. The Assistant ModelOwner must do the same.

### Managed Chatbot Blocks

Chatbots created/edited via FlowDrop should be identifiable:
- Use naming convention: `{assistant_id}_chatbot_{key}`
- OR add config flag: `managed_by_modeler: true`

This allows safe deletion of removed chatbots without affecting manually-created blocks.

## Key Files

| File | Role |
|------|------|
| `src/Plugin/ModelerApiModelOwner/Assistant.php` | NEW - Assistant ModelOwner |
| `src/Plugin/ModelerApiModeler/FlowDropAgents.php` | Modeler plugin (update) |
| `src/Service/WorkflowParser.php` | Workflow → Components (update) |
| `src/Controller/Api/AssistantSaveController.php` | Current custom save (migrate/remove) |
| `web/modules/contrib/ai_agents/src/Plugin/ModelerApiModelOwner/Agent.php` | Reference implementation |
| `web/modules/contrib/modeler_api/src/Api.php` | Core Modeler API |

## Open Questions (Resolved)

1. ~~Chatbot component type~~ → Use `COMPONENT_TYPE_ANNOTATION` (7) - semantically close as annotations are auxiliary/metadata elements
2. ~~Two-entity save~~ → Handle in `finalizeAddingComponents()`, same as Agent does for sub-agents
3. ~~Block entity as component~~ → **Yes** - chatbots use ANNOTATION type, fully inside the Component system
4. **Migration**: Existing assistants saved with custom controller will continue to work - same data, different save path

## Critical Fix: Chatbot Component Type Issue

### Problem
Originally tried to use custom `COMPONENT_TYPE_CHATBOT = 8` but `modeler_api/Component.php` line 68 validates:
```php
assert(in_array($type, Api::AVAILABLE_COMPONENT_TYPES), 'Invalid component type');
```
`Api::AVAILABLE_COMPONENT_TYPES` only contains 1-7, causing `AssertionError: Invalid component type`.

### Solution
Use `COMPONENT_TYPE_ANNOTATION` (7) for chatbots:

- **ANNOTATION** is semantically close (annotations are auxiliary/metadata elements)
- We don't use ANNOTATION for any other purpose in this model owner
- Chatbots stay **inside** the Component system - cleaner architecture

### Component Type Mapping (Final)

| FlowDrop Node Type | Component Type | Modeler API Constant |
|--------------------|----------------|---------------------|
| `assistant` | 1 | `COMPONENT_TYPE_START` |
| `tool` | 4 | `COMPONENT_TYPE_ELEMENT` |
| `agent` / `agent-collapsed` | 2 | `COMPONENT_TYPE_SUBPROCESS` |
| `chatbot` | 7 | `COMPONENT_TYPE_ANNOTATION` |
| edges | 5 | `COMPONENT_TYPE_LINK` |

### Files Changed
- `WorkflowParser.php`: `createChatbotComponent()` uses `Api::COMPONENT_TYPE_ANNOTATION`
- `Assistant.php`: Added `ANNOTATION => 'chatbot'` to `SUPPORTED_COMPONENT_TYPES`, added `handleChatbotComponent()`, updated `usedComponents()` to return chatbots as ANNOTATION

## References

- [Modeler API Documentation](https://www.drupal.org/project/modeler_api)
- [AI Agents ModelOwner](web/modules/contrib/ai_agents/src/Plugin/ModelerApiModelOwner/Agent.php)
- [AiAssistantForm](web/modules/contrib/ai/modules/ai_assistant_api/src/Form/AiAssistantForm.php)
