{**
 * controllers/tab/settings/appearance/form/sidebar.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Form fields for configuring the sidebars
 *
 *}
{if $isSiteSidebar}
    {assign var=component value="listbuilder.admin.siteSetup.AdminBlockPluginsListbuilderHandler"}
{else}
    {assign var=component value="listbuilder.settings.BlockPluginsListbuilderHandler"}
{/if}
{url|assign:blockPluginsUrl router=$smarty.const.ROUTE_COMPONENT component=$component op="fetch" escape=false}
{load_url_in_div id="blockPluginsContainer" url=$blockPluginsUrl}
