# FlowDrop Patch Notes

These patches document changes made to the **vendored FlowDrop source** in
`packages/flowdrop/`. They are **not** applied by Composer.

## Purpose

- Preserve local changes for review.
- Provide a clean way to apply fixes to an upstream FlowDrop clone.

## Workflow

1) Create patch from vendored source:
   - `git -C packages/flowdrop diff > patches/flowdrop/your-change.patch`
2) Add a short write‑up:
   - `patches/flowdrop/your-change.md`
3) Apply to upstream clone:
   - `git apply /path/to/patches/flowdrop/your-change.patch`
4) Commit + open PR in `d34dman/flowdrop`.

When upstream merges, update the vendored snapshot here and delete the patch
files if they are no longer needed.
