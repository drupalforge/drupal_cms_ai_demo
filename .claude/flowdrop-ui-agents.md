# FlowDrop UI Agents - Module Reference

Architecture and technical reference for the `flowdrop_ui_agents` module.

## Overview

Visual flow builder for AI Agents using FlowDrop UI and Modeler API. Provides drag-and-drop design of AI agent workflows with bidirectional conversion between FlowDrop format and AI Agent config entities.

**Drupal.org project**: https://www.drupal.org/project/flowdrop_ui_agents
**Issue queue**: https://www.drupal.org/project/issues/flowdrop_ui_agents

## Architecture

### Core Principle

FlowDrop UI is a pure visual editor - it saves directly to AI Agent/Assistant config entities, not separate workflow entities.

```
FlowDrop UI ──▶ Modeler API ──▶ AI Agent/Assistant Config
     │                               │
     │         (transformation)      │
     │                               ▼
     │                         Drupal Config
     │                               │
     ◀───────────────────────────────┘
            (load existing)
```

## Module Structure

```
flowdrop_ui_agents/
├── src/
│   ├── Controller/
│   │   ├── AssistantEditorController.php   # Assistant FlowDrop editor
│   │   └── Api/
│   │       ├── NodesController.php         # Sidebar API (tools + agents)
│   │       └── AssistantSaveController.php # Assistant save endpoint
│   ├── Hook/
│   │   └── EntityOperations.php            # Dropdown menu items
│   ├── Plugin/ModelerApiModeler/
│   │   └── FlowDropAgents.php              # Modeler API plugin
│   └── Service/
│       ├── AgentWorkflowMapper.php         # AI Agent ↔ FlowDrop conversion
│       └── WorkflowParser.php              # JSON → Modeler API Components
├── js/
│   └── flowdrop-agents-editor.js           # Save, notifications, UI
└── css/
    └── flowdrop-agents-editor.css          # Node styling
```

## Key URLs

- **Agent editor**: `/admin/config/ai/agents/{agent}/edit_with/flowdrop_agents`
- **Assistant editor**: `/admin/config/ai/ai-assistant/{assistant}/edit-flowdrop`

## API Endpoints

- `GET /api/flowdrop-agents/nodes` - All available tools and agents (108+)
- `GET /api/flowdrop-agents/nodes/by-category` - Grouped by category
- `GET /api/flowdrop-agents/nodes/{id}/metadata` - Single node details
- `POST /api/flowdrop-agents/assistant/{id}/save` - Save assistant + agent

## Node Types

| Type | Color | Icon | Description |
|------|-------|------|-------------|
| `agent` | Purple | `mdi:robot` | AI Agent (main or sub-agent) |
| `assistant` | Teal | `mdi:account-voice` | AI Assistant (wraps agent) |
| `agent-collapsed` | Light purple (dashed) | `mdi:robot-outline` | Collapsed sub-agent |
| `tool` | Orange | `mdi:tools` | Function call tool |

## Data Structures

### AI Agent Config

```php
$agent = [
  'id' => 'my_agent',
  'label' => 'My Agent',
  'description' => 'Used by triage agents',
  'system_prompt' => 'You are...',
  'secured_system_prompt' => '[ai_agent:agent_instructions]',
  'tools' => ['ai_agent:tool_id' => TRUE],
  'tool_settings' => ['ai_agent:tool_id' => ['return_directly' => 0]],
  'orchestration_agent' => FALSE,
  'triage_agent' => FALSE,
  'max_loops' => 3,
];
```

### FlowDrop Node

```javascript
{
  id: 'node-1',
  type: 'universalNode',
  position: { x: 100, y: 100 },
  data: {
    nodeId: 'node-1',  // REQUIRED for handle IDs
    label: 'Node Label',
    config: { ... },
    metadata: {
      id: 'plugin_id',
      inputs: [...],
      outputs: [...],
      configSchema: { properties: {...} }
    }
  }
}
```

## Modeler API Integration

### Two-Plugin Architecture

| Plugin Type | Role |
|-------------|------|
| **ModelOwner** | Owns config entities, defines components (in ai_agents module) |
| **Modeler** | Provides visual UI, parses data (our FlowDropAgents plugin) |

### Component Types

```php
Api::COMPONENT_TYPE_START = 1      // Agent node (main)
Api::COMPONENT_TYPE_SUBPROCESS = 2  // Sub-agent
Api::COMPONENT_TYPE_ELEMENT = 4     // Tool
Api::COMPONENT_TYPE_LINK = 5        // Edge/connection
```

## Assistant vs Agent

- **Agent**: Core entity with tools, system prompt, max loops
- **Assistant**: Wrapper around Agent that adds: LLM provider/model, history settings, roles, error message
- When editing Assistant, changes save to BOTH entities

### How Assistant → Agent → Tool Works

1. **Creating an Assistant** creates a backing **AI Agent** entity with the same ID
2. The backing agent is set to `orchestration_agent: TRUE`
3. Selected "sub-agents" are stored in the agent's `tools` array as `ai_agents::ai_agent::AGENT_ID`
4. When the Assistant runs, it actually runs via its backing Agent

### Plugin ID Formats

- **Agent tools**: `ai_agents::ai_agent::AGENT_ID` (double colons)
- **Tool plugins**: `tool:TOOL_ID` (single colon)
- **AI Agent tools**: `ai_agent:TOOL_NAME` (single colon)

## Common Gotchas

### JavaScript getWorkflow() Timing
Use `editorContainer.flowdropApp` NOT `window.currentFlowDropApp` - the navbar onclick handler fires before the global is set.

### Handle ID Format
- Input: `${nodeId}-input-${portId}`
- Output: `${nodeId}-output-${portId}`

### ConfigSchema Format
Must be JSON Schema format with `properties` object, not array:
```javascript
configSchema: {
  properties: {
    fieldName: { type: 'string', title: 'Label' }
  }
}
```

### Service Name
The modeler_api service is `plugin.manager.modeler_api.model_owner` (not with underscore).

### FlowDrop Uses API for Sidebar
FlowDrop calls API endpoints to populate sidebar (NOT drupalSettings):
```
FlowDrop Init → GET /api/flowdrop-agents/nodes → Populates sidebar
```

## Reference Code Locations

### AI Agents Core
```
web/modules/contrib/ai_agents/src/
├── Plugin/AiAgent/
├── Entity/AiAgent.php
├── PluginBase/AiAgentBase.php
└── Plugin/ModelerApiModelOwner/Agent.php  # Key reference!
```

### Key Code Paths
- Assistant Form Save: `ai_assistant_api/src/Form/AiAssistantForm.php:540-543`
- Agent Loading Tools: `ai_agents/src/PluginBase/AiAgentEntityWrapper.php:950`
