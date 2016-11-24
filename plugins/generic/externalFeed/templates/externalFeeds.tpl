{**
 * plugins/generic/externalFeed/templates/externalFeeds.tpl
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * External feed plugin -- displays the ExternalFeedGrid
 *
 *}
{url|assign:externalFeedGridUrl router=$smarty.const.ROUTE_COMPONENT component="plugins.generic.externalFeed.controllers.grid.ExternalFeedGridHandler" op="fetchGrid" escape=false}
{load_url_in_div id="externalFeedGridContainer" url=$externalFeedGridUrl}
