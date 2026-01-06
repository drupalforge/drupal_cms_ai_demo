# Support Multi-Agents and Assistants

- **Issue**: [#3563756](https://www.drupal.org/project/flowdrop_ui_agents/issues/3563756)
- **Branch**: `3563756-support-multi-agents-and`
- **Status**: COMPLETED - MR Created

## Summary

Fixed save functionality for Assistants when there are multiple sub-agents attached.

## What Was Implemented

### 1. Sub-Agent Saving with Topological Sort
Added `saveSubAgentsInOrder()` method to `AssistantSaveController.php` that:
- Uses DFS-based topological sort to save leaf agents first, parents last
- Ensures new agents created downstream can be attached to upstream agents
- Updates sub-agent fields: description, label, systemPrompt, maxLoops

### 2. Comprehensive Kernel Tests
Created 32 tests with 370 assertions in `tests/src/Kernel/`:

| Test File | Tests | Coverage |
|-----------|-------|----------|
| `FlowdropAgentsTestBase.php` | - | Base class with helpers |
| `SingleAgentSaveTest.php` | 6 | Single agent config saves |
| `AgentToolConnectionTest.php` | 8 | Tool connections via edges |
| `MultiAgentSaveTest.php` | 7 | Multi-agent hierarchy, sub-agent description saves |
| `AssistantSaveTest.php` | 11 | Assistant + Agent dual save |

### Key Test Cases
- `testSubAgentDescriptionSaves` - Directly tests the reported bug
- `testSubAgentToolsDontLeakToParent` - Tools stay isolated per agent
- `testThreeLevelHierarchyOnlySavesDirectChildren` - Correct parent-child relationships
- `testAgentIdentityPreserved` - No config cross-contamination

## Files Changed

| File | Change |
|------|--------|
| `src/Controller/Api/AssistantSaveController.php` | +223 lines - topological sort, sub-agent saving |
| `tests/src/Kernel/*.php` | New - 4 test classes + base class |
| `tests/assets/workflows/*.json` | New - test fixtures |

## Running Tests

```bash
ddev exec "SIMPLETEST_DB=sqlite://localhost/sites/default/files/.sqlite ./vendor/bin/phpunit -c web/core/phpunit.xml.dist modules/flowdrop_ui_agents/tests/src/Kernel/ --colors=always"
```

## Completed Tasks

- [x] Implement sub-agent saving with topological sort
- [x] Create Kernel test infrastructure
- [x] Write SingleAgentSaveTest (6 tests)
- [x] Write AgentToolConnectionTest (8 tests)
- [x] Write MultiAgentSaveTest (7 tests)
- [x] Write AssistantSaveTest (11 tests)
- [x] Fix sub-agent description not saving bug
- [x] Push to drupal.org issue fork
- [x] Create Merge Request

## Items Moved to New Issues

The following items from the original issue scope have been moved to [#3564034](https://www.drupal.org/project/flowdrop_ui_agents/issues/3564034):

- Tool Drawer categories to match Select Tools Widget
- Auto-attach tools when dragged onto Agent
- Fix initial tool spacing/overlap
- Add RAG Search tools (link to indexes)
- Node config panel ordering to match form priorities
- Improve tool config panel (force values)

---

*Completed: 2024-12-19*
