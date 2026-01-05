# Ability to Add Agents via FlowDrop UI

- **Issue**: [#3565646](https://www.drupal.org/project/flowdrop_ui_agents/issues/3565646)
- **Branch**: `3565646-ability-to-add-agents`
- **Status**: Planning

## Goal

Add "Create New" functionality to the FlowDrop UI sidebar, allowing users to create new Agents, Chatbots, and Assistants directly from the visual editor without leaving the flow.

## Requirements

### Create New Functionality
1. Ability to create a **new Agent** from sidebar
2. Ability to create a **new Chatbot** from sidebar
3. Ability to create a **new Assistant** (but only 1 per flow)
4. Nice UI that makes "Create New" clear near components

### Editing Scope Rules
5. **Agent editing**: Only edit downstream (agent + its tools), NOT upstream
6. **Assistant editing**: Edit BOTH upstream (chatbots) AND downstream (agents/tools)
7. When you edit an agent, you don't see/edit assistants that use it
8. When you edit an assistant, you see and can edit attached chatbots

### Constraints
9. Only 1 assistant per flow (validation)
10. Agents can be used by multiple assistants (but FlowDrop doesn't show this - each assistant is its own flow)

## UX Concepts

### Sidebar "Create New" UI

**Option A: Category Headers with + Button**
```
┌─────────────────────────┐
│ CHATBOTS            [+] │
│   └ My Unassigned Bot   │
│ AGENTS              [+] │
│   └ Triage Agent        │
│   └ Content Agent       │
│ TOOLS                   │
│   └ Entity List         │
│   └ Entity Save         │
└─────────────────────────┘
```

**Option B: "Create New" as First Item**
```
┌─────────────────────────┐
│ CHATBOTS                │
│   ✨ Create New Chatbot │
│   └ My Unassigned Bot   │
│ AGENTS                  │
│   ✨ Create New Agent   │
│   └ Triage Agent        │
└─────────────────────────┘
```

**Option C: Floating Action Button**
```
┌─────────────────────────┐
│ CHATBOTS                │
│   └ My Unassigned Bot   │
│ AGENTS                  │
│   └ Triage Agent        │
│                   [+]   │  <-- Opens menu
└─────────────────────────┘
```

### Create Flow

When user clicks "Create New Agent":
1. Opens inline form OR modal with basic fields:
   - Label (required)
   - Machine name (auto-generated, editable)
   - Description (optional)
2. Creates entity immediately with minimal config
3. Adds new node to canvas
4. User can then configure via node's config panel

### Assistant Creation (Special Case)

- Only show "Create New Assistant" if no assistant exists in flow
- OR: Gray out / hide if assistant already present
- When created, becomes the root node of the flow

## Tasks

### Phase 1: Backend - Create Endpoints
- [ ] Add `POST /api/flowdrop-agents/agent/create` endpoint
- [ ] Add `POST /api/flowdrop-agents/chatbot/create` endpoint  
- [ ] Add `POST /api/flowdrop-agents/assistant/create` endpoint
- [ ] Return new entity data in format ready for FlowDrop node

### Phase 2: Sidebar UI
- [ ] Add "Create New" UI elements to sidebar categories
- [ ] Implement click handler to open create form/modal
- [ ] Handle form submission → API call → add node to canvas
- [ ] Show loading state during creation

### Phase 3: Editing Scope
- [ ] Agent editor: Filter out upstream connections (don't load/show assistants)
- [ ] Assistant editor: Load upstream chatbots and include in graph
- [ ] Ensure save respects scope (agent save doesn't touch assistants)

### Phase 4: Validation
- [ ] Prevent adding second assistant to flow
- [ ] Show helpful error if user tries to add second assistant
- [ ] Validate required fields on create

### Phase 5: Polish
- [ ] Keyboard shortcuts for create actions
- [ ] Undo support for create (delete newly created entity)
- [ ] Success/error toast notifications

## Technical Notes

### Create Agent Endpoint

```php
// POST /api/flowdrop-agents/agent/create
public function createAgent(Request $request): JsonResponse {
  $data = Json::decode($request->getContent());
  
  $agent = AiAgent::create([
    'id' => $data['id'] ?? $this->generateMachineName($data['label']),
    'label' => $data['label'],
    'description' => $data['description'] ?? '',
    'system_prompt' => '',
    'tools' => [],
  ]);
  $agent->save();
  
  // Return in FlowDrop node format
  return new JsonResponse([
    'success' => true,
    'node' => $this->agentWorkflowMapper->createAgentNode($agent),
  ]);
}
```

### Create Chatbot Endpoint

```php
// POST /api/flowdrop-agents/chatbot/create
public function createChatbot(Request $request): JsonResponse {
  $data = Json::decode($request->getContent());
  
  $block = Block::create([
    'id' => $data['id'] ?? $this->generateMachineName($data['label']),
    'plugin' => 'ai_deepchat_block',
    'region' => $data['region'] ?? 'footer_bottom',
    'theme' => $data['theme'] ?? $this->getDefaultTheme(),
    'settings' => [
      'bot_name' => $data['label'],
      'ai_assistant' => '',  // Unassigned initially
      'placement' => 'bottom-right',
    ],
  ]);
  $block->save();
  
  return new JsonResponse([
    'success' => true,
    'node' => $this->createChatbotNode($block),
  ]);
}
```

### Editing Scope Implementation

**Agent Editor** (`/admin/config/ai/agents/{agent}/edit_with/flowdrop_agents`):
```php
public function loadAgentWorkflow(AiAgent $agent) {
  // Only load:
  // - The agent itself
  // - Its tools (downstream)
  // - Sub-agents it calls (downstream)
  // DO NOT load:
  // - Assistants that use this agent
  // - Chatbots connected to those assistants
}
```

**Assistant Editor** (`/admin/config/ai/ai-assistant/{assistant}/edit-flowdrop`):
```php
public function loadAssistantWorkflow(AiAssistant $assistant) {
  // Load:
  // - The assistant (as root node)
  // - Backing agent and its tools (downstream)
  // - Sub-agents (downstream)
  // - Chatbots linked to this assistant (upstream)
}
```

## Blockers / Questions

1. **Q**: Should "Create New" open a modal or inline form?
2. **Q**: What's the minimum required fields for each entity type?
3. **Q**: How to handle machine name collisions?
4. **Q**: Should newly created entities be immediately saved, or only on flow save?

## Dependencies

- Depends on #3565644 (Bring Chatbots into FlowDrop UI) for chatbot node type

## Reference Files

| File | Purpose |
|------|---------|
| `modules/flowdrop_ui_agents/src/Controller/Api/NodesController.php` | Add create endpoints |
| `modules/flowdrop_ui_agents/js/flowdrop-agents-editor.js` | Add create UI |
| `modules/flowdrop_ui_agents/src/Service/AgentWorkflowMapper.php` | Scope filtering |
