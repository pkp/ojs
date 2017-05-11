{**
 * templates/controllers/tab/admin/plugins/sitePlugins.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * List available plugins.
 *}
{url|assign:pluginGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.admin.plugins.AdminPluginGridHandler" op="fetchGrid" escape=false}
{load_url_in_div id="pluginGridContainer" url=$pluginGridUrl}
