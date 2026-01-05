# Support Multi-Agents and Assistants

- **Issue**: [#3563756](https://www.drupal.org/project/flowdrop_ui_agents/issues/3563756)
- **Branch**: `3563756-support-multi-agents-and`
- **Status**: In Progress

## Goal

Fix save functionality for Assistants and Agents when there are multiple agents, and complete the migration from the old GitHub dev repo to the proper Drupal.org module.

## Current Focus

**Fixing Save for Multi-Agent Assistants** - Save is currently broken when an Assistant has multiple sub-agents.

---

## Dependencies Required

Before implementing tests, these dependencies need updating:

| Package | Current | Required | Notes |
|---------|---------|----------|-------|
| `drupal/ai` | 1.2.4 | 1.3.x-dev | Core AI module (dev branch, not 2.x) |
| `drupal/ai_agents` | 1.2.1 | 1.3.x-dev | Agents functionality (dev branch) |
| `drupal/tool` | ❌ Not installed | 1.0.x-dev | [Tool module](https://www.drupal.org/project/tool) - provides function call tools |

### Composer Commands

```bash
# Combined - install all required dependencies
ddev composer require drupal/tool:1.0.x-dev drupal/ai:1.3.x-dev drupal/ai_agents:1.3.x-dev

# Then clear cache
ddev drush cr
```

---

## Automated Testing Strategy

### Overview

Tests use **Kernel tests** (PHPUnit with Drupal bootstrap, no browser required). This allows testing the full save pipeline with real entities without the complexity of browser automation.

### Why Kernel Tests (Not Browser Tests)?

The critical save logic is all in PHP:
- `WorkflowParser::parse()` - JSON parsing
- `WorkflowParser::toComponents()` - workflow → components
- `AssistantSaveController::updateAgent()` - tool extraction from edges
- `AssistantSaveController::updateAssistant()` - field mapping

The JS only:
1. Calls `getWorkflow()` from FlowDrop (third-party library)
2. POSTs JSON to endpoint
3. Shows toast messages

We trust FlowDrop's `getWorkflow()` works. Our tests focus on: **given this JSON, do entities save correctly?**

### Test Directory Structure

```
modules/flowdrop_ui_agents/tests/
├── src/
│   └── Kernel/
│       ├── FlowdropAgentsTestBase.php     # Base class with helpers
│       ├── SingleAgentSaveTest.php        # Scenario 1
│       ├── AgentToolConnectionTest.php    # Scenario 2
│       ├── MultiAgentSaveTest.php         # Scenario 3
│       └── AssistantSaveTest.php          # Scenario 4
└── assets/
    ├── workflows/                         # JSON workflow fixtures
    │   ├── single_agent_with_tool.json
    │   ├── multi_agent_workflow.json
    │   └── assistant_workflow.json
    └── config/                            # Entity fixtures (YAML)
        ├── ai_agents.ai_agent.test_agent_1.yml
        ├── ai_agents.ai_agent.test_agent_2.yml
        └── ai_assistant_api.ai_assistant.test_assistant.yml
```

### Test Scenarios

#### 1. SingleAgentSaveTest
**Goal**: Single Agent saves to the correct Agent entity

```php
// Setup: Create AI Agent entity
// Action: Build workflow JSON with agent + modified config, call save
// Assert: Agent entity has new values (system_prompt, max_loops, etc.)
```

#### 2. AgentToolConnectionTest
**Goal**: Connecting a tool to an Agent saves correctly

```php
// Setup: Create AI Agent entity (no tools)
// Action: Build workflow JSON with agent → tool edge, call save
// Assert: Agent's `tools` array contains the connected tool
```

#### 3. MultiAgentSaveTest
**Goal**: Two agents in workflow save to correct places (not mixed up)

```php
// Setup: Create TWO AI Agent entities
// Action: Build workflow JSON with both agents, each with different configs
// Assert: Each agent has correct values - agent_1 config on agent_1, etc.
```

#### 4. AssistantSaveTest
**Goal**: Assistant saves correctly to Assistant AND underlying Agent

```php
// Setup: Create AI Assistant + linked AI Agent
// Action: Build workflow JSON with assistant node, call AssistantSaveController::save()
// Assert: Both entities updated correctly
```

### Base Test Class

```php
abstract class FlowdropAgentsTestBase extends KernelTestBase {

  protected static $modules = [
    'system',
    'user',
    'node',
    'field',
    'key',
    'ai',
    'ai_agents',
    'ai_assistant_api',
    'tool',  // Required for function call tools
    'flowdrop_ui_agents',
  ];

  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installConfig(['ai_agents', 'ai_assistant_api']);
  }

  /**
   * Create a test AI Agent entity.
   */
  protected function createTestAgent(string $id, array $overrides = []): AiAgentInterface {
    $defaults = [
      'id' => $id,
      'label' => "Test Agent $id",
      'system_prompt' => 'Default prompt',
      'tools' => [],
      'max_loops' => 3,
    ];
    return $this->entityTypeManager
      ->getStorage('ai_agent')
      ->create(array_merge($defaults, $overrides))
      ->save();
  }

  /**
   * Build a workflow JSON structure for testing.
   */
  protected function buildWorkflowJson(array $nodes, array $edges = []): array {
    return [
      'id' => 'test_workflow',
      'nodes' => $nodes,
      'edges' => $edges,
    ];
  }
}
```

### Running Tests

```bash
# From web root
cd web

# Run all flowdrop_ui_agents tests
../vendor/bin/phpunit modules/contrib/flowdrop_ui_agents/tests/

# Run specific test
../vendor/bin/phpunit modules/contrib/flowdrop_ui_agents/tests/src/Kernel/SingleAgentSaveTest.php

# With DDEV
ddev exec "cd web && ../vendor/bin/phpunit modules/contrib/flowdrop_ui_agents/tests/"
```

### Future: Browser Tests (Optional)

If end-to-end testing is needed later:
- Use `FunctionalJavascriptTestBase`
- Render FlowDrop editor, drag nodes, make connections
- Click save button
- Complex and slow - defer until core functionality is stable

---

## All Tasks from Issue

These are all items from the issue - many will become separate issues:

### Immediate Priority

#### 1. Update Dependencies
- [ ] Add `drupal/tool:1.0.x-dev` to composer
- [ ] Update `drupal/ai` to 1.3.x-dev (not 2.x)
- [ ] Update `drupal/ai_agents` to 1.3.x-dev
- [ ] Run `ddev drush cr` and verify site works

#### 2. Implement Automated Tests
- [ ] Create test directory structure in `modules/flowdrop_ui_agents/tests/`
- [ ] Write `FlowdropAgentsTestBase.php` base class
- [ ] Write `SingleAgentSaveTest.php` - single agent saves correctly
- [ ] Write `AgentToolConnectionTest.php` - tool connections save
- [ ] Write `MultiAgentSaveTest.php` - two agents save to correct places
- [ ] Write `AssistantSaveTest.php` - assistant + agent both save

#### 3. Fix Multi-Agent Save Bug
- [ ] Run tests to identify exact failure mode
- [ ] **Fix multi-agent save** - Save broken for Assistants with multiple agents
- [ ] Verify fix with tests

#### 4. Additional Testing
- [ ] Test editing agents within an assistant flow
- [ ] Test assistants with multiple sub-agents
- [ ] Handle opening an Agent attached to an Assistant (should open Assistant instead?)

### New Features (Future Issues)
- [ ] Add Deepchat Chatbot node type for Assistants
- [ ] Tool Drawer categories to match Select Tools Widget
- [ ] Node config panel ordering to match form priorities (hide advanced)
- [ ] Prompt text boxes full-screen editor
- [ ] Auto-attach tools when dragged onto Agent
- [ ] Fix initial tool spacing/overlap
- [ ] Add RAG Search tools (link to indexes)
- [ ] Improve tool config panel
- [ ] AI Assistant to help set up agents (stretch goal)

### Migration
- [x] Module moved from GitHub to drupal.org
- [ ] Verify all functionality from old repo is present

## Technical Notes

### What's Currently Working (from Phase 7)

- FlowDrop visual editor for Agents at `/admin/config/ai/agents/{agent}/edit_with/flowdrop_agents`
- FlowDrop visual editor for Assistants at `/admin/config/ai/ai-assistant/{assistant}/edit-flowdrop`
- Multi-agent visualization with 3 modes: Expanded, Grouped, Collapsed
- Assistant node type with teal color, `mdi:account-voice` icon
- Save notifications (toast messages)
- Unsaved changes indicator
- Sidebar shows 108+ tools + agents

### Known Save Bug

**Problem**: When saving an Assistant with multiple sub-agents, the save doesn't work correctly.

**Previous Fix Applied** (may need revisiting):
- Used edges to determine direct children, not just node types
- Only processing nodes directly connected FROM the main assistant/agent node

**Edge-Based Tool Detection Pattern**:
```php
// Find direct children using edges, not node iteration
$directChildNodeIds = [];
foreach ($edges as $edge) {
    if ($edge['source'] === $mainNodeId) {
        $directChildNodeIds[] = $edge['target'];
    }
}
```

### Key Files

| File | Purpose |
|------|---------|
| `src/Controller/Api/AssistantSaveController.php` | Assistant save endpoint |
| `src/Service/AgentWorkflowMapper.php` | AI Agent ↔ FlowDrop conversion |
| `src/Service/WorkflowParser.php` | JSON → Components |
| `js/flowdrop-agents-editor.js` | Save, notifications, UI |

### Assistant vs Agent Save Flow

```
Assistant Editor
     │
     ├─→ AssistantEditorController::edit()
     │      └─→ Converts agent workflow, injects assistantConfig
     │      └─→ Changes main node to nodeType='assistant'
     │
     └─→ AssistantSaveController::save()
            ├─→ findAssistantNodeConfig()
            ├─→ updateAgent() - Uses EDGES to find direct children
            └─→ updateAssistant() - Maps camelCase to snake_case
```

### Test URLs

- **Agent**: `/admin/config/ai/agents/agent_bundle_lister/edit_with/flowdrop_agents`
- **Assistant**: `/admin/config/ai/ai-assistant/bundle_lister_assistant/edit-flowdrop`

## Blockers / Questions

1. What exactly is broken with multi-agent save? Need to test and identify specific failure mode.
2. Should opening an Agent that belongs to an Assistant redirect to the Assistant, or show a warning?

## Reference: Multi-Agent Implementation Details

**View Modes:**
- Expanded: Flat view - sub-agents and tools as regular nodes
- Grouped: Sub-agents as visual container boxes
- Collapsed: Sub-agents as single node with badge "[X tools]"

**Save Order (Topological Sort):**
1. Leaf agents (no sub-agents) saved first
2. Intermediate agents next
3. Parent agents last

**Recursion Limit:** Max depth 3 levels

**Sub-Agent Detection:**
```php
if (str_starts_with($toolId, 'ai_agents::ai_agent::')) {
  $subAgentId = str_replace('ai_agents::ai_agent::', '', $toolId);
}
```

## Session Log

### 2024-12-18
- Created plan file for issue
- Migrated context from old CLAUDE-BRANCH.md and CLAUDE-NOTES.md
- Added automated testing strategy (Kernel tests, no browser required)
- Identified dependency requirements: Tool module (dev), AI 1.3, AI Agents 1.3
- Documented 4 test scenarios covering save functionality
