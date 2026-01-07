# FlowDrop Source + Build Workflow

This repo uses a **vendored FlowDrop source snapshot** and **commits the built
assets** used by Drupal. We only rebuild FlowDrop when we change its source.

## Summary

- Source lives in `packages/flowdrop/` (vendored snapshot of `d34dman/flowdrop`).
- Drupal loads **built assets** from:
  - `web/modules/contrib/flowdrop/modules/flowdrop_ui/build/flowdrop/`
- DrupalForge deployments use the committed build assets (no npm build during
  deploy).

## Why this approach

- DrupalForge stays simple (git clone + Drupal install/cache).
- Local DDEV setup is unchanged unless FlowDrop source is modified.
- We can still open upstream issues/PRs to `d34dman/flowdrop` for general fixes.

## Local change workflow (when modifying FlowDrop)

1) Edit FlowDrop source in `packages/flowdrop/`.
2) Build and sync the Drupal‑friendly bundle:
   - `scripts/build-flowdrop.sh`
3) The script copies build outputs into:
   - `web/modules/contrib/flowdrop/modules/flowdrop_ui/build/flowdrop/`
4) Commit **both** the source snapshot (if updated) and the built assets.

Notes:
- Drupal uses the **IIFE** build (`flowdrop.iife.js`) rather than ES modules.
- Keep build output paths consistent so DrupalForge and DDEV both load the same
  artifacts.

## Upstream GitHub workflow

If a change should be shared, open an issue/PR in
`https://github.com/d34dman/flowdrop`.

### Upstreaming a local change

We keep **patch files** alongside the repo to make upstreaming easy.

1) Create a patch from the vendored source:
   - `git -C packages/flowdrop diff > patches/flowdrop/your-change.patch`
2) Add a short write‑up:
   - `patches/flowdrop/your-change.md`
3) Clone the upstream FlowDrop repo in a separate location:
   - `git clone git@github.com:d34dman/flowdrop.git`
4) Apply the patch to that clone:
   - `git apply /path/to/patches/flowdrop/your-change.patch`
5) Commit and open a PR in `d34dman/flowdrop`.

When the upstream PR merges, update the vendored snapshot in this repo and
remove the patch + md if it’s no longer needed.

## Non‑goals

- We do **not** use Composer patches for FlowDrop JS.
- We do **not** run npm builds during DrupalForge deployments.
