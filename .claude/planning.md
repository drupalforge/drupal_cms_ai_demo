# Historical Planning & Roadmap

Historical context on how the module was built and overall project direction.

## Completed Phases

### Phase 1-5: Foundation (Complete)
- Modeler API integration pattern research
- FlowDrop → Config transformation
- Config → FlowDrop transformation
- Tool drawer population
- Edit & update flows
- Config panels and tool connections

### Phase 6: Module Creation (Complete)
- Created `flowdrop_ui_agents` module
- FlowDropAgents Modeler plugin
- AgentWorkflowMapper and WorkflowParser services
- Save functionality via Modeler API

### Phase 7: Multi-Agent & Assistants (Complete)
- Multi-agent visualization (expanded/grouped/collapsed modes)
- AI Assistant support with dedicated editor
- Save notifications and unsaved indicator
- "Edit with FlowDrop" dropdown integration

## Current Phase

### Phase 8: Polish & Production Ready
See active plans in `.claude/plans/` for current work.

Key items:
- Fix multi-agent save
- Test edge cases
- New node types (Deepchat)
- UI/UX improvements
- RAG integration

## Architectural Decisions

| Decision | Choice | Rationale |
|----------|--------|-----------|
| Save target | AI Agent config only | FlowDrop UI is pure UI, no separate workflow storage |
| Transformation | Via Modeler API | Follows proven pattern from ai_agents module |
| Tools | Tool module only | Future-proof; converts to function calls under the hood |
| Multi-agent | Supported | Connections define orchestration |
| Chatbot connection | Manual | Assistant + Agents in FlowDrop; Chatbot linked separately |

## Success Criteria

### MVP (Achieved)
1. ✅ Create flow with Agent and Tools
2. ✅ Save creates real AI Agent config
3. ✅ Open existing Agent in FlowDrop
4. ✅ Edit and save changes

### Full Implementation (In Progress)
1. ⬜ Multi-agent flows with orchestration (save broken)
2. ✅ All valid Tools in drawer
3. ⬜ Full round-trip testing
4. ✅ Works with Assistants
5. ⬜ Deepchat integration

## Parked Work

### Layout Toggle for Compact/Normal Nodes
**Status**: Parked - requires FlowDrop modifications

FlowDrop's node type system requires `metadata.supportedTypes` to include the target type. Our nodes only declare their primary type.

### Port Config Warning
**Status**: Low priority - cosmetic

Warning "Invalid port config received from API" appears on init. FlowDrop falls back to defaults which work fine.
