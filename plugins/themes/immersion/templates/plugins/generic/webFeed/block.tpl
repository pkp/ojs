{**
 * plugins/generic/webFeed/templates/block.tpl
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Feed plugin navigation sidebar.
 *
 *}
<div class="pkp_block block_web_feed col-md-3">
	<h2>{translate key="journal.currentIssue"}</h2>
	<div class="content">
		<ul>
			<li>
				<a href="{url router=$smarty.const.ROUTE_PAGE page="gateway" op="plugin" path="WebFeedGatewayPlugin"|to_array:"atom"}">
					<img src="{$baseUrl}/lib/pkp/templates/images/atom.svg" alt="{translate key="plugins.generic.webfeed.atom.altText"}">
				</a>
			</li>
			<li>
				<a href="{url router=$smarty.const.ROUTE_PAGE page="gateway" op="plugin" path="WebFeedGatewayPlugin"|to_array:"rss2"}">
					<img src="{$baseUrl}/lib/pkp/templates/images/rss20_logo.svg" alt="{translate key="plugins.generic.webfeed.rss2.altText"}">
				</a>
			</li>
			<li>
				<a href="{url router=$smarty.const.ROUTE_PAGE page="gateway" op="plugin" path="WebFeedGatewayPlugin"|to_array:"rss"}">
					<img src="{$baseUrl}/lib/pkp/templates/images/rss10_logo.svg" alt="{translate key="plugins.generic.webfeed.rss1.altText"}">
				</a>
			</li>
		</ul>
	</div>
</div>
