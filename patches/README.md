# Patches

Local patches that Composer applies via `patches.json`.

## Structure

```
patches/
└── {vendor}/
    └── {project}/
        ├── {patch-name}.patch    # The actual patch file
        └── {patch-name}.md       # Issue description & upstream status
```

## How Patches Are Applied

Patches are registered in `patches.json` and applied by the `cweagans/composer-patches` plugin during `composer install`.

Example `patches.json` entry:
```json
{
    "patches": {
        "drupal/flowdrop": {
            "Category weight-based sorting": "./patches/drupal/flowdrop/category-weight-sorting.patch"
        }
    }
}
```

## Current Patches

### drupal/flowdrop

| Patch | Purpose | Upstream Status |
|-------|---------|-----------------|
| `category-weight-sorting.patch` | Adds `categoryWeight` support to sidebar sorting | Pending submission |
| `document-build-files.patch` | Documents IIFE vs ES module builds in README | Pending submission |

## Patch Documentation (.md files)

Each `.patch` file should have a corresponding `.md` file documenting:

1. **Problem**: What issue this patch solves
2. **Solution**: How the patch solves it
3. **Files Modified**: Which files the patch changes
4. **Testing**: How to verify the patch works
5. **Upstream Status**: Whether it's been submitted/accepted upstream

When a patch is accepted upstream, remove both the `.patch` and `.md` files and update `patches.json`.

## Creating New Patches

1. Make changes to the target module
2. Generate patch: `git diff > ../patches/vendor/project/patch-name.patch`
3. Create documentation: `patch-name.md`
4. Register in `patches.json`
5. Test: `composer install` (patch should apply cleanly)
