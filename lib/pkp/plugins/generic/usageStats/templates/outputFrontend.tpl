{**
 * plugins/generic/usageStats/templates/outputFrontend.tpl
 *
 * Copyright (c) 2013-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Add HTML markup for a usage stats graph on the frontend
 *
 * @uses $pubObjectType The type of object this graph will respresent.
 *   Example: PublishedArticle
 * @uses $pubObjectId The id of the object tihs graph will represent.
 *}

<div class="item downloads_chart">
	<h3 class="label">
		{translate key="plugins.generic.usageStats.downloads"}
	</h3>
	<div class="value">
		<canvas class="usageStatsGraph" data-object-type="{$pubObjectType}" data-object-id="{$pubObjectId|escape}"></canvas>
		<div class="usageStatsUnavailable" data-object-type="{$pubObjectType}" data-object-id="{$pubObjectId|escape}">
			{translate key="plugins.generic.usageStats.noStats"}
		</div>
	</div>
</div>
