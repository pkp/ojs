{**
 * plugins/generic/externalFeed/block.tpl
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * External feed plugin navigation sidebar.
 *
 *}
{foreach from=$externalFeeds item=externalFeed}
	<div class="pkp_block block_external_feed">
		<span class="title">{$externalFeed.title|truncate:20}</span>
		<div class="content">
			<ul>
			{foreach from=$externalFeed.items item=feedItem}
				<li>
					<a href="{$feedItem->get_permalink()}" target="_blank">{$feedItem->get_title()|truncate:40}</a>
				</li>
			{/foreach}
			</ul>
		</div>
	</div>
{/foreach}
