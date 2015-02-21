{**
 * plugins/generic/webFeed/templates/block.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Feed plugin navigation sidebar.
 *
 *}
<div class="block" id="sidebarWebFeed">
	<span class="blockTitle">{translate key="journal.currentIssue"}</span>
	<a href="{url page="gateway" op="plugin" path="WebFeedGatewayPlugin"|to_array:"atom"}">
	<img src="{$baseUrl}/plugins/generic/webFeed/templates/images/atom10_logo.gif" alt="{translate key="plugins.generic.webfeed.atom.altText"}" border="0" /></a>
	<br/>
	<a href="{url page="gateway" op="plugin" path="WebFeedGatewayPlugin"|to_array:"rss2"}">
	<img src="{$baseUrl}/plugins/generic/webFeed/templates/images/rss20_logo.gif" alt="{translate key="plugins.generic.webfeed.rss2.altText"}" border="0" /></a>
	<br/>
	<a href="{url page="gateway" op="plugin" path="WebFeedGatewayPlugin"|to_array:"rss"}">
	<img src="{$baseUrl}/plugins/generic/webFeed/templates/images/rss10_logo.gif" alt="{translate key="plugins.generic.webfeed.rss1.altText"}" border="0" /></a>
</div>
