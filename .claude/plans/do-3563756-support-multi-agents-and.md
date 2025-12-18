# Support Multi-Agents and Assistants

- **Issue**: [#3563756](https://www.drupal.org/project/flowdrop_ui_agents/issues/3563756)
- **Branch**: `3563756-support-multi-agents-and`
- **Status**: In Progress

## Goal

Fix save functionality for Assistants and Agents when there are multiple agents, and complete the migration from the old GitHub dev repo to the proper Drupal.org module.

## Current Focus

**Fixing Save for Multi-Agent Assistants** - Save is currently broken when an Assistant has multiple sub-agents.

## All Tasks from Issue

These are all items from the issue - many will become separate issues:

### Immediate Priority
- [ ] **Fix multi-agent save** - Save broken for Assistants with multiple agents
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
