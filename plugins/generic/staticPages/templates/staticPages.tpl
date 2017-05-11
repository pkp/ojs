{**
 * templates/staticPages.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Static pages plugin -- displays the StaticPagesGrid.
 *}
{url|assign:staticPageGridUrl router=$smarty.const.ROUTE_COMPONENT component="plugins.generic.staticPages.controllers.grid.StaticPageGridHandler" op="fetchGrid" escape=false}
{load_url_in_div id="staticPageGridContainer" url=$staticPageGridUrl}
