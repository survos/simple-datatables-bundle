# Working on Simple Datatables Bundle

## Direction

Read the migration guide in `README.md` before changing this bundle or migrating a consumer. Simple Datatables is supported, is not deprecated, and remains appropriate for lighter table needs. Migrate only applications selected by the user or whose requirements justify the change. The shared API Grid path uses `survos/grid-bundle` as the shared DataTables base and `survos/api-grid-bundle` for API Platform behavior. Grid is heavier than simple-datatables; shared integration is the reason for the choice. Do not claim this bundle has been removed or every consumer migrated.

## Choosing a table library

- If the app already uses API Grid and needs a fast in-memory datatable, use Grid: it is already included in the shared API Grid dependency graph.
- If an in-memory table is sufficient and the app does not already use API Grid, ask the user to choose Grid versus simple-datatables before installing or migrating. Do not infer that Grid is always preferred.
- An explicit choice already made by the user remains valid; do not ask again for the same app.

## Consumer migration

- Inventory actual component calls, direct Stimulus mounts, PHP Column objects, custom styles and event handlers before porting features.
- Migrate one application/page at a time. Preserve row links, custom Twig blocks, translation and scrolling behavior. Rename `perPage` to `pageLength`, remove `backend`, and check all nonstandard options.
- Prefer the Grid Twig component. Its controller must live on a stable wrapper, not the table that DataTables reparents.
- Grid owns DataTables core, extensions and their pins. API-specific pagination, facets and rendering belong in API Grid. Do not add a second DataTables setup here as part of a migration.
- Keep controllers lazy and CSS controller-local. Do not introduce global DataTables CSS autoimports.
- Check transitive requirements with `composer why` before removing this bundle or Pentiminax. Do not remove assets still needed by another backend.

## Composer and local development

Use Packagist for public packages. Reserve Satis for private packages; do not route public Survos packages through Satis. Always install bundles normally from their published Composer repositories, then run `/path/to/mono/link /path/to/application` for local development. NEVER add `type: path` repositories or invented version aliases to application `composer.json` or `composer.lock`. Do not hand-edit vendor source or copy bundle code into vendor. Ensure the lockfile contains remotely installable packages; a successful linked test does not verify the published release.

## Verification and shared checkout

Test search, sorting, pagination, page sizes, empty rows, custom cells, links and Turbo reconnects. Check browser errors and actual asset downloads; importmap pin counts alone do not measure page weight. Test API-specific behavior separately. Verify without local links before deployment.

Preserve unrelated checkout changes and commit only the relevant work. Do not edit tabler-bundle as a side effect of a table migration. Keep README examples aligned with the registered controller identifier (`survos--simple-datatables-bundle--table`) while legacy users remain supported.
