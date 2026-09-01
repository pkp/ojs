# Eidos Theme for OJS

An official theme from PKP that is highly configurable.

## Usage

> This assumes you know [how to build a custom OJS theme](https://docs.pkp.sfu.ca/pkp-theming-guide/en/).

Install the dependencies.

```
npm install
```

Run vite in local development mode.

```
npm run start
```

Build vite assets before deploying your theme to production.

```
npm run build
```

## Temporary Docs

### Layouts

Uses the `Layout` component to pass page title, description, meta tags, etc to pages.

```
<!-- indexJournal.blade -->
<x-layout
    title="{{ $currentContext->getLocalizedName() }}"
    description="{{ $currentContext->getLocalizedData('description') }}"
>
    <h1>Page</h1>
</x-layout>
```

[Attributes](https://laravel.com/docs/11.x/blade#component-attributes) passed to the layout will be assigned to the `<body>` tag. Some attributes are defined in `layout.blade`.

```
<!-- indexJournal.blade -->
<x-layout
    title="{{ $currentContext->getLocalizedName() }}"
    description="{{ $currentContext->getLocalizedData('description') }}"
    class="my-custom-page-class"
>
    <h1>Page</h1>
</x-layout>
```

```
<!-- Output -->
<body dir="ltr" class="pkp-page-index pkp-page-op my-custom-page-class">
```

A `head` [slot](https://laravel.com/docs/11.x/blade#slots) exists to pass custom content for the `<head>`.

```
<x-layout
    title="{{ $currentContext->getLocalizedName() }}"
    description="{{ $currentContext->getLocalizedData('description') }}"
>
    <x-slot:head>
        <meta name="test" content="hello there.">
        <script>console.log('hello')</script>
    </x-slot:head>

    <h1>Page</h1>
</x-layout>
```

### Article Metadata Blocks

> I think there is still some work to do to decide the right structure for this feature. For now, there is a new `MetadataBlocksRegistry` which is accessed at `$templateMgr->metadataBlocks`.
>
> The current setup is designed for a future where blocks are registered on demand. However, in practice, the theme registers all of its options in the `init()` function, so all blocks are registered with every request anyway. It would be nice to find a setup where theme options aren't registered on the backend, except the website settings page.

Every template that uses the default `Layout` component can load and display metadata blocks in a template on the article landing page.

```php
<x-layout>

    ...

    @foreach ($metadata() as $block)
        <x-dynamic-component
            :component="$block->component"
            headingTag="h3"
            :title="$block->title"
            :description="$block->description"
            :$publication
            :submission="$article"
        />
    @endforeach

    ...

</x-layout>
```

Each block has a template in the `components/metadata` directory. Themes can override this template with their own.

```php
{{-- templates/components/metadata/keywords.blade --}}

<div>
    <h3>My Keywords</h3>
    <div>
        @foreach ($publication->getLocalizedData('keywords') as $keyword)
            {{ $keyword['name'] }}@if(!$loop->last){{ __('common.commaListSeparator') }}@endif
        @endforeach
    </div>
</div>
```

Use the `$headingTag` variable to use the correct `<h*>` tag when displaying this block.

```php
{{-- templates/components/metadata/keywords.blade --}}

<div>
    <{{ $headingTag }}>My Keywords</{{ $headingTag }}>
    <div>
        @foreach ($publication->getLocalizedData('keywords') as $keyword)
            {{ $keyword['name'] }}@if(!$loop->last){{ __('common.commaListSeparator') }}@endif
        @endforeach
    </div>
</div>
```

Use the `<x-metadata-blocks.default>` component to show the title and content in a consistent format.

```php
{{-- templates/components/metadata/keywords.blade --}}

<x-metadata-blocks.default
    id="keywords"
    title="{{ __('article.subject') }}"
    :$headingTag
>
    @foreach ($publication->getLocalizedData('keywords') as $keyword)
        {{ $keyword['name'] }}@if(!$loop->last){{ __('common.commaListSeparator') }}@endif
    @endforeach
</x-metadata-blocks.default>
```

Themes can register their own article metadata blocks by implementing the `HasMetadataBlocks` plugin interface.

```php
class ExampleTheme extends ThemePlugin implements HasMetadataBlocks
{
    public function registerMetadataBlocks(MetadataBlocksRegistry $blocks): void
    {
        $blocks->register(
            new MetadataBlock(
                id: 'example',
                title: 'Example Metadata',
                /**
                 * This uses the component path syntax in Blade to
                 * specify the template to use for this metadata block.
                 *
                 * This example would load the template at:
                 *
                 * /plugins/themes/exampleTheme/templates/components/metadata/example.blade
                 *
                 * @see https://laravel.com/docs/11.x/blade#anonymous-components
                 */
                component: 'metadata-blocks.example',
            )
        );
    }
}
```

Plugins (not themes) need to use the correct [component namespace](https://github.com/pkp/pkp-lib/issues/9968) to load a template from the plugin's directory.

```php
class ExamplePlugin extends GenericPlugin implements HasMetadataBlocks
{
    public function registerMetadataBlocks(MetadataBlocksRegistry $blocks): void
    {
        $blocks->register(
            new MetadataBlock(
                id: 'example',
                title: 'Example Metadata',
                /**
                 * Notice the plugin namespace, exampleplugin::,
                 * which is needed to load the correct component
                 * template.
                 *
                 * In this example, the copmonent template would
                 * be loaded in:
                 *
                 * /plugins/<category>/<plugin>/templates/components/metadata/example.blade
                 */
                component: 'exampleplugin::metadata.example',
            )
        );
    }
}
```

When registering a `MetadataBlock`, you can optionally pass in a `loader` callback function. Use this to pass data to the template. (Data registered this way is available to all templates, so be sure to use a unique prefix for plugin data.)

```php
$blocks->register(
    new MetadataBlock(
        id: 'example',
        title: 'Example Metadata',
        component: 'exampleplugin::metadata.galley',
        /**
         * The callback function receives the current Publication and Submission
         *
         * If other data is needed from the template, you can access it through
         * the TemplateManager.
         *
         * Example:
         *
         * $templateMgr = TemplateManager::get(Application::get()->getRequest());
         * $templateMgr->getTemplateVar('metricsByType');
         */
        loader: function(Publication $publication, Submission $submission) {
            $daysSince = getDaysSince($publication->getData('datePublished'));
            view()->share('exampleDaysSincePublished', $daysSince);
        }
    )
);
```

A theme should be able to unregister any block. The active theme's callback is called after all other plugins, including parent themes, so it should be able to unregister any other block.

```php
class ExampleTheme extends ThemePlugin implements HasMetadataBlocks
{
    public function registerMetadataBlocks(MetadataBlocksRegistry $blocks): void
    {
        $blocks->unregister('doi');
    }
}
```

Blocks can also be registered by accessing the metadata registry through the `TemplateManager`. However, depending on when they are registered, they may not be registered early enough to be exposed to the theme for theme options and unregistration.

```php
$templateMgr = TemplateManager::getManager(Application::get()->getRequest());
$templateMgr->metadataBlocks->register(...);
```

### Notice

Use the notice component to add a message, warning, or error.

```html
<x-notice>
    <div>This is a preview and has not been published.</div>
</x-notice>
```

Add an optional title and actions.

```html
<x-notice>
    <x-slot:title>
        Preview
    </x-slot:title>
    <div>This is a preview and has not been published.</div>
    <x-slot:actions>
        <a class="button" href="...">
            View Submission
        </a>
        <a class="button" href="...">
            Go to homepage
        </a>
    </x-slot>
</x-notice>
```

### Body Classes

Classes are assigned to the `<body>` element which can be used to adapt styles based on theme options. These include:

- `site-width-full | site-width-fixed` to adapt the header based on the Site Width option.
- `font-<name>`, `font-titles-<name>`, and `font-actions-<name>` to apply custom styles for fonts. When no custom font options are enabled, the `<name>` will be `default`. Use this to style the default [Noto Sans](https://fonts.google.com/noto/specimen/Noto+Sans) font, which supports variable weights and widths.

## System Pages

Several page templates have been moved to a special "system" layout that is not themed to match the rest of the theme. These pages include:

- Login, registration, etc
- message.tpl and error.tpl
- Payment, subscription, etc

The goal is to move these eventually to `pkp-lib` / `ojs`, so that custom themes don't have to style them (they can if they want).

All files for these pages are in separate subdirectories so that they can be easily relocated to core:

- `templates/components/system`
- `src/system.js`
- `src/css/system`
- `src/js/system`

These assets have a separate build output in `vite.config.js` and are loaded with the `['contexts' => ['system']]` argument (eg - `$theme->addStyle(..., ['contexts' => ...])`).

### system-message.tpl

The `error.tpl` and `message.tpl` templates have been combined into a common `system-message.tpl`, and these changes have been applied to OJS and pkp-lib. A new `$templateMgr->displaySystemMessage()` function provides a typed helper function to prevent the template parameters from drifting over time, as happened with `error.tpl` and `message.tpl`.

For now, the `system-message.tpl` file in pkp-lib still uses the frontend layout. But once the system layout is moved into core this template can be replaced by the one in the Eidos theme.

## Credit

This library is distributed under GPL 3.0. The Vite integration is based on [php-vite](https://github.com/mindplay-dk/php-vite) by [@mindplay-dk](https://github.com/mindplay-dk).
