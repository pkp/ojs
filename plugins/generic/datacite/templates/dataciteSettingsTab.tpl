{**
 * templates/dataciteSettingsTab.tpl
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * DataciteExportPlugin plugin -- displays the StaticPagesGrid.
 *}

<tab id="datacite-settings" label="{translate key="plugins.importexport.datacite.settings.label"}">
    {capture assign=dataciteSettingsGridUrl}
        {url router=\PKP\core\PKPApplication::ROUTE_COMPONENT component="grid.settings.plugins.settingsPluginGridHandler" op="manage" plugin="DataciteExportPlugin" category="importexport" verb="index" escape=false}
    {/capture}
    {load_url_in_div id="dataciteSettingsGridContainer" url=$dataciteSettingsGridUrl}
</tab>
