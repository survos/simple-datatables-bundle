# Survos Simple Datatables Bundle

Integrates the Simple Datatables library (https://github.com/fiduswriter/simple-datatables/) as a Symfony UX Stimulus controller, and provides Twig components for common rendering.

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
<table class="table" {{ stimulus_controller('@survos/simple-datatables/table', { perPage: 5, sortable: true }) }}>
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
     <table class="table" {{ stimulus_controller('@survos/simple-datatables/table', {perPage: 5, sortable: true}) }}>
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
