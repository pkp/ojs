{**
 * templates/frontend/pages/issue.tpl
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Display a landing page for a single issue. It will show the table of contents
 *  (toc) or a cover image, with a click through to the toc.
 *
 * @uses $issue Issue The issue
 * @uses $issueIdentification string Label for this issue, consisting of one or
 *       more of the volume, number, year and title, depending on settings
 * @uses $issueGalleys array Galleys for the entire issue
 * @uses $showGalleyLinks bool Show galley links to users without access?
 *}
{include file="frontend/components/header.tpl" pageTitleTranslated=$issueIdentification}

<div class="page page_issue">
	{* @todo look into this and find an appropriate place for it *}
	{if $issue}
		{foreach from=$pubIdPlugins item=pubIdPlugin}
			{if $issue->getPublished()}
				{assign var=pubId value=$pubIdPlugin->getPubId($issue)}
			{else}
				{assign var=pubId value=$pubIdPlugin->getPubId($issue, true)}{* Preview rather than assign a pubId *}
			{/if}
			{if $pubId}
				{$pubIdPlugin->getPubIdDisplayType()|escape}:
				{if $pubIdPlugin->getResolvingURL($currentJournal->getId(), $pubId)|escape}
					<a id="pub-id::{$pubIdPlugin->getPubIdType()|escape}" href="{$pubIdPlugin->getResolvingURL($currentJournal->getId(), $pubId)|escape}">
						{$pubIdPlugin->getResolvingURL($currentJournal->getId(), $pubId)|escape}
					</a>
				{else}
					{$pubId|escape}
				{/if}
			{/if}
		{/foreach}
	{/if}

	{* Display a message if no current issue exists *}
	{if !$issue}
		{include file="frontend/components/breadcrumbs_issue.tpl" currentTitleKey="current.noCurrentIssue"}
		{include file="frontend/components/notification.tpl" type="warning" messageKey="current.noCurrentIssueDesc"}

	{* Display an issue with the Table of Contents *}
	{elseif $showToc}
		{include file="frontend/components/breadcrumbs_issue.tpl" currentTitle=$issueIdentification}
		{include file="frontend/objects/issue_toc.tpl"}

		{* Display a legend describing the open/restricted access icons *}
		{if $showGalleyLinks && $issueGalleys && $issue->getNumArticles()}
			{include file="frontend/components/accessLegend.tpl"}
		{/if}

	{* Display an issue without a Table of Contents *}
	{* @todo create an appropriate issue_cover.tpl object template for this *}
	{else}
		{if $issueId}
			{url|assign:"currentUrl" page="issue" op="view" path=$issueId|to_array:"showToc"}
		{else}
			{url|assign:"currentUrl" page="issue" op="current" path="showToc"}
		{/if}
		<ul class="menu">
			<li><a href="{$currentUrl}">{translate key="issue.toc"}</a></li>
		</ul>
		{if $coverPagePath}<div id="issueCoverImage"><a href="{$currentUrl}"><img src="{$coverPagePath|escape}{$issue->getFileName($locale)|escape}"{if $coverPageAltText != ''} alt="{$coverPageAltText|escape}"{else} alt="{translate key="issue.coverPage.altText"}"{/if}{if $width} width="{$width|escape}"{/if}{if $height} height="{$height|escape}"{/if}/></a></div>{/if}
		<div id="issueCoverDescription">{$issue->getLocalizedCoverPageDescription()|strip_unsafe_html|nl2br}</div>
	{/if}
</div>

{include file="common/frontend/footer.tpl"}
