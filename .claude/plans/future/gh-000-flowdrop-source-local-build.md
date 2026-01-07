# FlowDrop Local Source + Patch Workflow

- **Issue**: N/A (local planning only)
- **Branch**: `gh-000-flowdrop-source-local-build`
- **Status**: Planning

## Goal

Enable local patching of FlowDrop (Svelte + @xyflow/svelte) by bringing its
source into this repo (outside `web/`), building it locally, and wiring Drupal
to use the custom build while keeping a clean upstream issue/PR workflow on the
FlowDrop GitHub repo.

## Tasks

- [ ] Inventory current FlowDrop integration
- [ ] Design repo layout + build pipeline
- [ ] Define GitHub workflow for upstream patches

## Technical Notes

### 1) Inventory current FlowDrop integration

Focus on understanding which built assets Drupal loads and how they are
packaged.

- Locate FlowDrop library usage in this repo and note exact asset paths:
  - `web/modules/contrib/flowdrop/modules/flowdrop_ui/build/flowdrop/`
  - Confirm Drupal uses `flowdrop.iife.js` (not `flowdrop.es.js`)
- Identify where this library is declared (library definition file) and any
  current patches or overrides in `patches/` or `patches.json`.
- Identify how FlowDrop UI Agents depends on FlowDrop assets (JS/CSS) and where
  those references live in module or theme code.

### 2) Design repo layout + build pipeline (no build yet)

Decide how the FlowDrop source lives in the demo repo, and how builds will
produce the assets Drupal needs.

- Options to evaluate:
  - Git submodule at `vendor/flowdrop/` (or `packages/flowdrop/`)
  - Vendor a tarball of the FlowDrop repo (checked in or via script)
  - Use a git subtree for easier syncing
- Build outputs should land outside `web/` and then be copied/symlinked into the
  Drupal module asset path expected by the library.
- Need a simple target for Drupal:
  - `flowdrop.iife.js` + any CSS bundles in a known build folder
- Decide the canonical build command(s), e.g. `npm run build:drupal`.

### 3) Define GitHub workflow for upstream patches

Make sure FlowDrop changes are tracked upstream and this demo repo can consume
them.

- Open GitHub issues/PRs in `d34dman/flowdrop` for any fixes.
- Keep local patches minimal by tracking a specific commit or tag.
- Decide pinning strategy in this repo:
  - Submodule pinned to commit
  - Script that fetches a known tag/commit and builds
  - Patch files applied during build

## Blockers / Questions

- Decision: commit built FlowDrop assets (no npm build during DrupalForge).

## Deployment Notes (Two Build Paths)

### DrupalForge (production)

- Use prebuilt FlowDrop assets committed to the repo.
- Deployment should only need to pull git and run Drupal install/cache steps.
- Build output path Drupal loads:
  - `web/modules/contrib/flowdrop/modules/flowdrop_ui/build/flowdrop/`
- Expected artifacts:
  - `flowdrop.iife.js` (Drupal uses IIFE build)
  - any CSS assets from the FlowDrop build

### DDEV (local)

- Local setup uses env vars / recipe inputs for API keys.
- We should keep FlowDrop source under a top‑level folder (e.g. `packages/flowdrop/`)
  and optionally provide a manual build script for local dev.
- Local build should copy build artifacts into the same Drupal path as DrupalForge
  so both workflows use the identical assets.

## Options Snapshot (for decision)

### Git submodule

**Pros**: clean upstream linkage, easy to update to a specific commit.
**Cons**: extra git workflow complexity, onboarding friction.

### Git subtree

**Pros**: no submodule friction, repo contains source.
**Cons**: heavier updates/merges, history noise.

### Vendored source (copied snapshot)

**Pros**: simple, no git tooling needed, easy for Drupal‑style workflows.
**Cons**: manual update process, easy to drift from upstream.

## Proposed Layout (source vs build)

```
packages/
  flowdrop/             # FlowDrop source (Svelte)
web/modules/contrib/flowdrop/modules/flowdrop_ui/build/flowdrop/
  flowdrop.iife.js      # Build output used by Drupal
  flowdrop.es.js        # Build output for bundlers (not used by Drupal)
  *.css                 # Build output styles
```

## Proposed Update Flow (high level)

1) Make changes in `packages/flowdrop/`.
2) Build locally (`npm run build:drupal` or equivalent).
3) Copy/sync outputs into `web/modules/contrib/flowdrop/modules/flowdrop_ui/build/flowdrop/`.
4) Commit the built assets for DrupalForge.
5) Open upstream issue/PR in `d34dman/flowdrop` if it’s a general fix.
