{**
 * block.tpl
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Theses feed plugin navigation sidebar.
 *
 * $Id$
 *}
<div class="block" id="sidebarThesisFeed">
	<span class="blockTitle">{translate key="plugins.generic.thesis.manager.theses"}</span>	
	<a href="{url page="gateway" op="plugin" path="ThesisFeedGatewayPlugin"|to_array:"atom"}">
	<img src="{$baseUrl}/plugins/generic/thesisFeed/templates/images/atom10_logo.gif" alt="{translate key="plugins.generic.thesisfeed.atom.altText"}" border="0" /></a>
	<br/>
	<a href="{url page="gateway" op="plugin" path="ThesisFeedGatewayPlugin"|to_array:"rss2"}">
	<img src="{$baseUrl}/plugins/generic/thesisFeed/templates/images/rss20_logo.gif" alt="{translate key="plugins.generic.thesisfeed.rss2.altText"}" border="0" /></a>
	<br/>
	<a href="{url page="gateway" op="plugin" path="ThesisFeedGatewayPlugin"|to_array:"rss"}">
	<img src="{$baseUrl}/plugins/generic/thesisFeed/templates/images/rss10_logo.gif" alt="{translate key="plugins.generic.thesisfeed.rss1.altText"}" border="0" /></a>
</div>
