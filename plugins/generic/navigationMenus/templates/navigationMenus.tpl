{**
 * templates/navigationMenus.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * NavigationMenus plugin -- displays the NavigationMenusGrid.
 *}
{url|assign:navigationMenusGridUrl router=$smarty.const.ROUTE_COMPONENT component="plugins.generic.NavigationMenus.controllers.grid.NavigationMenusGridHandler" op="fetchGrid" escape=false}
{load_url_in_div id="NavigationMenusGridContainer" url=$navigationMenusGridUrl}
