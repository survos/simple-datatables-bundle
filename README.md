# Survos Simple Datatables Bundle

Integrates the Simple Datatables library (https://github.com/fiduswriter/simple-datatables/) as a Symfony UX Stimulus controller, and provides Twig components for common rendering.

## Migrating to Grid Bundle

Simple Datatables remains supported and is appropriate when a lighter table library meets the application’s needs. It is not deprecated. If an application already uses `survos/api-grid-bundle`, use `survos/grid-bundle` for its fast in-memory tables: the shared Grid dependency is already included. If an in-memory table is sufficient and the application does not already use API Grid, ask the application owner to choose Grid or simple-datatables before migrating. Use API Grid when API-backed pagination and filtering are required. Grid is heavier than simple-datatables, but gives both paths one DataTables implementation and Bootstrap 5 / Tabler theme. Optional extensions load lazily. This guide describes an explicit application migration; installing Grid does not automatically replace existing components.

### 1. Inventory and install

Find uses of `simple_datatables`, `simple_item_grid`, `Survos\SimpleDatatables`, direct Stimulus mounts, custom CSS and JavaScript hooks. Check both `backend="simple"` and `backend="ux"` tables. Record representative routes and importmap pin counts before changing them.

Install a published Grid release that includes the shared base, `extensions` and `options` component properties. Confirm the release is available through your configured Composer repositories; an older release can install successfully without those features.

```bash
composer require survos/grid-bundle
php bin/console importmap:install
php bin/console cache:clear
```

For local bundle development, **install normally first**, then run the mono checkout's linking tool:

```bash
/path/to/mono/link /path/to/application
```

Never add Composer `type: path` repositories or fabricated package-version overrides to application manifests or lockfiles. `mono/link` changes the local vendor tree; deployment must use published packages and a portable Composer lockfile. Locally linked code is not proof that the locked release contains the same features.

### 2. Replace a table component

Before:

```twig
<twig:simple_datatables :data="products" :columns="['name', 'price']"
    perPage="20" backend="ux" :trans="false" scrollY="">
    <twig:block name="price">{{ row.price|number_format(2) }}</twig:block>
</twig:simple_datatables>
```

After:

```twig
<twig:grid :data="products" :columns="['name', 'price']"
    :pageLength="20" :trans="false" scrollY="" :info="true">
    <twig:block name="price">{{ row.price|number_format(2) }}</twig:block>
</twig:grid>
```

| Existing usage | Grid migration |
| --- | --- |
| `simple_datatables` | `grid` |
| `perPage` | `pageLength` |
| `backend="simple"` or `backend="ux"` | Remove; Grid uses DataTables |
| `data`, named `columns`, column blocks | Preserve; local blocks still receive `row`, `column`, `idx` |
| `search`, `info`, `useDatatables`, `condition`, `tableId`, `tableClasses` | Supported; set explicitly where behavior matters |
| Translation and scrolling defaults | Set `trans` and `scrollY` explicitly to preserve the old page |
| `activate` | Remove; use `useDatatables` to control enhancement |
| `Survos\SimpleDatatables\Model\Column` objects | Rebuild as `Survos\Grid\Model\Column` or supported column arrays; do not pass legacy objects |
| `simple_item_grid` | Review and migrate to `item_grid`; its field blocks receive `data`, not `row` |

A `remoteUrl` grid fetches its collection once and paginates/searches in the browser. Supply explicit named columns. Local PHP Twig blocks do not render remote rows. Use API Grid for server-side pagination, facets and browser-side Twig rendering; it requires API Platform and is a separate migration.

Do not merely rename the controller on a manually authored `<table>`. Prefer the Twig component, which puts the Grid controller on a stable wrapper with a table target. DataTables moves the table during initialization; mounting the controller on that table can cause repeated connect/disconnect cycles. Grid's controller identifier is `survos--grid-bundle--grid`.

### 3. Configure assets and optional features

Keep the controller lazy and CSS imports inside the controller:

```json
{
  "controllers": {
    "@survos/grid-bundle": {
      "grid": {"enabled": true, "fetch": "lazy", "autoimport": {}}
    }
  }
}
```

Grid owns DataTables pins through its `assets/package.json` → `symfony.importmap`. Compare existing application versions with that manifest: `importmap:install` downloads existing pins but does not upgrade all stale versions. Use `importmap:require package@version` for the required corrections. Do not add DataTables CSS to global `controllers.json` autoimports.

Port only features the application uses. For example, responsive behavior needs both the extension and its configuration:

```twig
<twig:grid :data="products" :columns="['name', 'price']"
    :extensions="['responsive']" :options="{responsive: true}" />
```

Review old simple-datatables/Pentiminax events, options and CSS selectors individually; these are not interchangeable APIs. See the [Grid README](https://github.com/survos/grid-bundle#readme) for supported extensions and the [API Grid README](https://github.com/survos/api-grid-bundle#readme) for the server-driven path.

### 4. Verify, then remove legacy dependencies

Check search, both sort directions, page sizes, pagination, empty data, links, custom cells and Turbo navigation away/back. Test any remote endpoint and requested extensions. Inspect the browser console and network: a plain Grid should not download unused extensions, and pages without tables should not load DataTables unless another consumer requires it. Compare pin counts before/after separately from actual network downloads.

Showcase's `/browse` page provides a local reference over 23 sites; tree-demo's `/playground/topics` compares Grid backends. Showcase's base-Grid smoke test does not establish API Grid facet or pagination correctness. Repeat verification using the published packages without local links before deployment.

Once all consumers have moved:

```bash
composer why survos/simple-datatables-bundle
composer why pentiminax/ux-datatables
composer remove survos/simple-datatables-bundle
```

A transitive consumer such as `survos/folio-bundle` may still require the old bundle. Migrate that consumer first; do not force removal. Remove direct Pentiminax requirements only after its own call sites are migrated. Inspect recipe cleanup and remove unused legacy bundle configuration, routes, controller registrations and importmap pins only when no remaining consumer needs them. Keep this cleanup scoped to the application being migrated.

## UX DataTables backend (opt-in)

The bundle also includes a client-side backend using `pentiminax/ux-datatables` 1.x.
Existing tables keep the Simple DataTables backend unless selected explicitly:

```twig
<twig:simple_datatables backend="ux" :data="products" :columns="['name', 'price']" perPage="10">
    <twig:block name="price">
        <strong>${{ row.price|number_format(2) }}</strong>
    </twig:block>
</twig:simple_datatables>
```

Or select it for all components:

```yaml
survos_simple_datatables:
    backend: ux
```

Cells and column blocks are rendered by PHP Twig, not js-twig or twig-browser.
The small `ux` controller captures that HTML before delegating initialization,
sorting, searching, paging, and Turbo lifecycle handling to UX DataTables. It
persists the complete dataset for reconnects rather than recapturing the visible
page. No API Platform resource or per-table PHP class is required.

For `remoteUrl`, supply explicit named columns and an endpoint returning a JSON
array of objects. It is fetched once for client-side processing. Server-rendered
column blocks apply to local `data` only; remote column blocks, facets, and API
Platform processing are outside this backend's current scope. `simple_item_grid`
remains a separate definition-list component.

When upgrading an existing application, update Composer dependencies so
`Pentiminax\\UX\\DataTables\\PentiminaxDataTablesBundle` is installed and enabled.
Let its Symfony UX recipe register its assets. Also refresh this bundle's
controller registration: the canonical package key is now
`@survos/simple-datatables-bundle`, with `table` and `ux` controllers. Update any
old `@survos/simple-datatables` controller/style references accordingly. The `ux`
controller imports the upstream AssetMapper module
`@pentiminax/ux-datatables/controller.js`. Register that local module in existing
AssetMapper applications:

```bash
php bin/console importmap:require '@pentiminax/ux-datatables/controller.js' --path='./vendor/pentiminax/ux-datatables/assets/dist/controller.js'
```

With UX DataTables 1.0, check that its recipe uses the bundle class
`Pentiminax\UX\DataTables\PentiminaxDataTablesBundle` and route resource
`@PentiminaxDataTablesBundle/config/routes.php`; an older contributed recipe
still uses `DataTablesBundle`.

This preserves the existing component API as a migration layer. A focused
upstream contribution would add existing-HTML-table support and then a lightweight
Twig component, without requiring Survos or FieldBundle.

```bash
composer req survos/simple-datatables-bundle
```

## Stimulus Controller (Tables)

To turn any HTML `<table>` into a datatable, simply add the stimulus controller to the tag:

```twig
<table class="table" {{ stimulus_controller('@survos/simple-datatables-bundle/table', { perPage: 5, search: true }) }}>
```

## Twig Component: `simple_datatables`

This component renders a `<table>` and wires up the datatable stimulus controller.

```twig
{% set columns = [
    { name: 'id' },
    { name: 'title', title: 'name' },
    'brand',
    'price',
] %}

<twig:simple_datatables
    perPage="20"
    :columns="columns"
    :data="products"
>
    <twig:block name="price">
        ${{ row.price|number_format(2) }}
    </twig:block>
</twig:simple_datatables>
```

Notes:
- `columns` can be strings (`'price'`) or arrays passed to `Survos\SimpleDatatables\Model\Column` (e.g. `{ name: 'price', title: 'Price' }`).
- Inside a column block you typically get `row`, `column`, `idx`.

## Twig Component: `simple_item_grid`

`simple_item_grid` renders a single item/record as a definition list (`<dl>`), which is handy for a “show” page.

Component: `Survos\SimpleDatatables\Components\ItemGridComponent`.
Template: `templates/components/item.html.twig`.

### Basic usage

`simple_item_grid` renders the fields you list in `columns` for a single associative array or object passed as `data`:

```twig
<twig:simple_item_grid
    :data="product"
    :columns="['title', 'brand', 'price']"
/>
```

### Columns

Columns can be strings or `Column`-style arrays. For the item grid template, `name` and `title` are the relevant fields.

```twig
{% set columns = [
    { name: 'title', title: 'Name' },
    { name: 'price', title: 'Price' },
    'brand',
] %}

<twig:simple_item_grid :data="product" :columns="columns" />
```

### Excluding fields

The `exclude` option is used when the component auto-generates columns. Auto-generation currently only kicks in when `data` is a list of rows and `columns` is omitted.

```twig
<twig:simple_item_grid :data="items" exclude="internalNotes,ssn" />
```

### Custom rendering with blocks

For custom rendering, define a block named after the column’s `name`. The block receives `data`.

```twig
<twig:simple_item_grid :data="product" :columns="['title','price']">
    <twig:block name="price">
        ${{ data.price|number_format(2) }}
    </twig:block>
</twig:simple_item_grid>
```

### Stimulus

The wrapper `<div>` can be wired to a stimulus controller via `stimulusController`.
By default it uses `@survos/grid/item_grid`.

## Complete Project

Cut and paste to create a new Symfony project with a dynamic, searchable datatable, without writing a single line of JavaScript. No webpack or build step either.

```bash
symfony new simple-datatables-demo --webapp  && cd simple-datatables-demo
rm .git -rf
composer config extra.symfony.allow-contrib true
composer req survos/simple-datatables-bundle

bin/console make:controller Simple -i
cat > templates/simple.html.twig <<END
{% extends 'base.html.twig' %}

{% block body %}
     <table class="table" {{ stimulus_controller('@survos/simple-datatables-bundle/table', {perPage: 5, search: true}) }}>
        <thead>
        <tr>
            <th>abbr</th>
            <th>name</th>
            <th>number</th>
        </tr>
        </thead>
        <tbody>
        {% for j in 1..12 %}
            <tr>
                <td>{{ j |date('2023-' ~ j ~ '-01') |date('M') }}</td>
                <td>{{ j |date('2023-' ~ j ~ '-01') |date('F') }}</td>
                <td>{{ j }}</td>
            </tr>
        {% endfor %}
        </tbody>
    </table>
{% endblock %}
END
symfony server:start -d
symfony open:local --path=/simple
```

Or even easier, use the Twig component. To simplify the example we're going to add the `fetch` and `json_decode` functions to Twig; normally that would be done in the controller, but the copy/paste is faster this way.

```bash
composer require zenstruck/twig-service-bundle
cat > config/packages/zenstruck_twig_service.yaml <<END
zenstruck_twig_service:
  functions:
    fetch: file_get_contents # available as "fn_fetch()" in twig
    json_decode: json_decode
END

cat > templates/simple.html.twig <<'END'
{% extends 'base.html.twig' %}

{% block body %}
    {% set columns = [
        {name: 'id'},
        {name: 'title', title: 'name'},
        'brand',
        'price'
    ] %}
    <twig:simple_datatables
            perPage="20"
            :caller="_self"
            :columns="columns"
            :data="fn_json_decode(fn_fetch('https://dummyjson.com/products')).products"
    >
        <twig:block name="price">
            ${{ row.price|number_format(2) }}
        </twig:block>

    </twig:simple_datatables>
{% endblock %}
END
symfony server:start -d
symfony open:local --path=/simple

```

## Troubleshooting a static table

Use `@survos/simple-datatables-bundle/table` in `stimulus_controller()`. Older README examples omitted `-bundle`, producing an identifier that did not match the registered UX controller. Rendered `data-controller` attributes alone do not prove that Stimulus connected. See [issue #2](https://github.com/survos/simple-datatables-bundle/issues/2).

The rendered identifier should be `survos--simple-datatables-bundle--table`. Check that `assets/controllers.json` enables `table` under `@survos/simple-datatables-bundle`, the application starts Stimulus, and the browser console has no missing-import errors. Run `php bin/console importmap:install` after installation. The controller exposes `perPage` and `search`; it does not expose a `sortable` Stimulus value.

For Survos applications that also need API-backed tables, `survos/grid-bundle` provides the shared DataTables base used by `survos/api-grid-bundle`. It is a heavier foundation than simple-datatables, chosen for shared behavior and integration across the platform. This is a migration direction, not a claim that existing simple-datatables applications have already migrated.
