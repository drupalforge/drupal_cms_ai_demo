# FlowDrop UI Agents - General Improvements (#3564034)

- **Issue**: [#3564034](https://www.drupal.org/project/flowdrop_ui_agents/issues/3564034)
- **Branch**: `3564034-general-improvements-to`
- **Status**: Implementation complete, needs review and submission

## Issue Requirements

From the issue text:

1. Tool Drawer uses same Categories as Select Tools Widget
2. Drag tool on top of Agent auto-attaches and positions nicely
3. Initial tool load spacing (no overlap)
4. RAG search tools with index linking (leave as-is, defer expansion)
5. Node config forms match Drupal form ordering (advanced hidden)
6. Tool config improvements including force values

## Implementation Status

| Requirement | Status | Notes |
|-------------|--------|-------|
| Categories match Select Tools | Done | Uses FunctionGroup metadata + categoryWeight system |
| Auto-attach on drop | Done | Needs correctness fix (isToolNode bug) |
| Tool spacing | Done | toolSpacingY increased to 180px |
| RAG tools | Done | Leave as-is, out of scope for expansion |
| Config panel ordering | Done | Schema reordered, Property Setup section added |
| Force values | Done | Flatten/expand mapping implemented |

---

## Submission Blockers (Must Fix)

### 1. JS: `isToolNode()` always returns true

**File**: `js/flowdrop-agents-editor.js` line ~529

**Problem**: Function ends with `|| true`, making it always true for non-agents. This will:
- Misclassify nodes
- Break auto-attach detection and "existing tool count" math
- Cause unexpected positioning or connections

**Current**:
```javascript
function isToolNode(node) {
    const cat = node.data?.category || node.category;
    if (cat === 'Agent' || cat === 'agents' || node.type === 'agent') return false;
    return node.data?.nodeType === 'tool' || node.data?.metadata?.type === 'tool' || true;
}
```

**Fix**: Remove `|| true`:
```javascript
function isToolNode(node) {
    const cat = node.data?.category || node.category;
    if (cat === 'Agent' || cat === 'agents' || node.type === 'agent') return false;
    return node.data?.nodeType === 'tool' || node.data?.metadata?.type === 'tool';
}
```

### 2. JS: Event listener cleanup in detach()

**File**: `js/flowdrop-agents-editor.js`

**Problem**: `setupDragOverHighlight()` adds document-level `dragstart`/`dragend` listeners but `detach()` doesn't remove them. In Drupal behaviors, attach/detach symmetry matters because of AJAX refreshes, BigPipe, off-canvas, etc. Leaked listeners will stack.

**Fix**: Store handler references on `editorContainer` and remove in `detach()`:

```javascript
// In setupDragOverHighlight():
editorContainer.dragStartHandler = function(e) { ... };
editorContainer.dragEndHandler = function(e) { ... };
document.addEventListener('dragstart', editorContainer.dragStartHandler);
document.addEventListener('dragend', editorContainer.dragEndHandler);

// In detach():
if (container.dragStartHandler) {
    document.removeEventListener('dragstart', container.dragStartHandler);
    delete container.dragStartHandler;
}
if (container.dragEndHandler) {
    document.removeEventListener('dragend', container.dragEndHandler);
    delete container.dragEndHandler;
}
```

### 3. JS: Hardcoded endpoint URLs

**File**: `js/flowdrop-agents-editor.js` lines ~62-67

**Problem**: `baseUrl: '/api/flowdrop-agents'` and `token_url: '/session/token'` break subdirectory installs and alternative base paths.

**Fix**: Use `drupalSettings.path.baseUrl` prefix:
```javascript
const endpointConfig = {
    baseUrl: drupalSettings.path.baseUrl + 'api/flowdrop-agents',
    // ...
};

const tokenUrl = drupalSettings.path.baseUrl + 'session/token';
```

### 4. Remove `.DS_Store` from staging

**Status**: Done (added to .gitignore in commit `2e8cf06`)

---

## Recommended Improvements (Should Fix)

### A. JS: Polling loops are heavy

Multiple `setInterval` loops run indefinitely (auto-attach 1s, truncation removal, sidebar styling 2s). FlowDrop doesn't expose reliable events, so polling is acceptable for now, but should be bounded:

- Stop after N tries or once found
- Or use `MutationObserver` with disconnect on success

### B. PHP: AgentWorkflowMapper style nits

`estimateAgentHeight()` has `if (!$enabled) continue;` without braces and some indentation oddities. Drupal coding standards expect braces for control structures.

### C. WorkflowParser changes: scope question

The `WorkflowParser.php` changes for property restrictions flattening are **tool config / save semantics**, which is borderline for this UX-focused issue. If we keep them:
- They need tests (kernel test for roundtrip)
- Reviewers will scrutinize the persistence layer

The changes ARE needed to make force values work, so we keep them but ensure they're tested.

---

## Architecture Notes

For Drupal.org reviewers:

- **JS DOM hacks are isolated**: `setupSidebarStyling()` handles all category renaming/styling
- **API contracts are stable**: nodes consistently have `category`, `categoryWeight`, etc.
- **Future improvement**: Move "node catalog building" into a service so both `NodesController` and editor controllers use the same codepath

---

## Auto-Attach Behavior Specification

### Drop Rules

| Dragged Item | Drop Target | Behavior |
|--------------|-------------|----------|
| Tool | Agent node | Auto-attach + position in grid |
| Tool | Assistant node | No auto-attach (drop normally) |
| Tool | Canvas (empty) | Drop normally, no auto-attach |
| Agent | Agent node | Auto-attach as sub-agent |
| Agent | Assistant node | Auto-attach as sub-agent |
| Agent | Canvas (empty) | Drop normally |
| Assistant | Anything | Cannot be dropped (stays in place) |

### Position Calculation

When auto-attaching a tool to an agent:
1. Find existing tools connected to that agent
2. Calculate grid position using:
   - `toolOffsetX`: 350px right of agent
   - `toolSpacingY`: 180px between rows
   - `toolColWidth`: 300px between columns
   - Dynamic columns: `Math.max(2, Math.ceil(Math.sqrt(toolCount)))`
3. Place new tool at next available grid slot

---

## Tests to Add

### Kernel Test: Property Restrictions Roundtrip

**File**: `tests/src/Kernel/PropertyRestrictionsTest.php`

```php
public function testForceValuePersists(): void {
    // Create agent with tool having force_value restriction
    $agent = $this->createTestAgent('test_force_agent', [
        'tools' => ['tool:entity_list' => TRUE],
        'tool_settings' => [
            'tool:entity_list' => [
                'property_restrictions' => [
                    'entity_type' => [
                        'action' => 'force_value',
                        'force_value' => 'node',
                    ],
                ],
            ],
        ],
    ]);

    // Map to workflow
    $workflow = $this->agentWorkflowMapper->agentToWorkflow($agent);

    // Find tool node and verify flattened keys
    $toolNode = $this->findToolNode($workflow, 'tool:entity_list');
    $this->assertEquals('Force value', $toolNode['data']['config']['prop_entity_type_restriction']);
    $this->assertEquals('node', $toolNode['data']['config']['prop_entity_type_values']);

    // Save workflow back
    // ... (via AssistantSaveController or WorkflowParser)

    // Reload and verify restrictions persisted
    $reloaded = $this->reloadAgent('test_force_agent');
    $restrictions = $reloaded->get('tool_settings')['tool:entity_list']['property_restrictions'];
    $this->assertEquals('force_value', $restrictions['entity_type']['action']);
    $this->assertEquals('node', $restrictions['entity_type']['force_value']);
}
```

### Kernel Test: Category Weight in API Response

**File**: `tests/src/Kernel/NodesControllerTest.php`

```php
public function testNodesHaveCategoryWeight(): void {
    $controller = $this->container->get('flowdrop_ui_agents.nodes_controller');
    $response = $controller->getNodes();
    $data = json_decode($response->getContent(), TRUE);

    foreach ($data['data'] as $node) {
        $this->assertArrayHasKey('categoryWeight', $node);
        $this->assertIsInt($node['categoryWeight']);
    }

    // Agents should have weight -100
    $agentNode = $this->findNodeByType($data['data'], 'agent');
    $this->assertEquals(-100, $agentNode['categoryWeight']);
}
```

---

## Documentation Updates

Update `modules/flowdrop_ui_agents/README.md`:

### Add to Features section:

```markdown
- **Category Ordering**: Sidebar categories match the Select Tools widget ordering
  using a weight system. Agents appear first (weight -100), followed by tool
  categories (weight 0).

- **Auto-Attach**: Dropping a tool onto an Agent node automatically creates a
  connection and positions the tool in a grid layout relative to existing tools.

- **Tool Configuration**: Full support for property restrictions including:
  - Force value: Lock a property to a specific value
  - Only allow certain values: Restrict to a list of allowed values
  - Hide property: Hide from LLM and logging
  - Override description: Custom description sent to LLM
```

### Add to Known Issues section:

```markdown
### Auto-Attach Limitations

Auto-attach only works when dropping tools directly onto Agent nodes. Dropping
onto Assistant nodes or empty canvas areas will not auto-attach. This is
intentional to give users control over complex workflows.
```

---

## Commit Organization for MR

Aim for 2-3 focused commits for reviewability:

### Commit 1: Sidebar categories + native styling
```
fix(sidebar): ensure Sub-Agent Tools category uses FlowDrop native agents styling

- Use FlowDrop's internal 'agents' category for native teal styling
- JS renames header text to "Sub-Agent Tools" for display
- categoryWeight system ensures agents appear first (-100)
- Tool categories get orange wrench icons via JS injection
```

### Commit 2: Auto-attach + grid placement + baseUrl fix
```
feat(ux): auto-attach tools on drop + grid placement

- Auto-connect tools when dropped on Agent nodes (not Assistant)
- Calculate grid position relative to existing tools
- Add drag-over highlighting for drop targets
- Fix isToolNode() always-true bug
- Fix event listener cleanup in detach()
- Use drupalSettings.path.baseUrl for endpoint URLs
```

### Commit 3: Tool config + tests + docs
```
feat(tool-config): property restrictions and config panel ordering

- Flatten property_restrictions to prop_* keys for FlowDrop UI
- Rebuild nested structure on save via WorkflowParser
- Reorder config schema to match Drupal form priority
- Add toggle switches and collapsible Property Setup section
- Add kernel tests for property restrictions roundtrip
- Add kernel tests for categoryWeight in API
- Update README with new features
```

---

## Pre-Submission Checklist

### Blockers (must fix)
- [ ] Fix `isToolNode()` bug (remove `|| true`)
- [ ] Fix event listener cleanup in `detach()`
- [ ] Fix hardcoded URLs (use `drupalSettings.path.baseUrl`)
- [x] Add `.DS_Store` to .gitignore (done)

### Code quality
- [ ] Run `phpcs` on changed PHP files
- [ ] Fix `AgentWorkflowMapper::estimateAgentHeight()` brace style
- [ ] Consider bounding polling loops (optional)

### Testing
- [ ] Run kernel tests: `ddev exec phpunit modules/flowdrop_ui_agents/tests`
- [ ] Add `PropertyRestrictionsTest` for force_value roundtrip
- [ ] Add `NodesControllerTest` for categoryWeight
- [ ] Manual test: drag tool onto Agent node → auto-attach
- [ ] Manual test: drag tool onto Assistant node → no auto-attach
- [ ] Manual test: categories match Select Tools widget
- [ ] Manual test: force values save and reload correctly

### Documentation
- [ ] Update README with features and known issues

### Final
- [ ] Reorganize commits per structure above
- [ ] Verify all tests pass
- [ ] Submit MR to Drupal.org
