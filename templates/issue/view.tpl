{**
 * templates/issue/view.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * View issue -- This displays the issue TOC or title page, as appropriate,
 * *without* header or footer HTML (see viewPage.tpl)
 *}
{if $subscriptionRequired && $showGalleyLinks && $showToc}
	<div id="accessKey">
		<img src="{$baseUrl}/lib/pkp/templates/images/icons/fulltext_open_medium.gif" alt="{translate key="article.accessLogoOpen.altText"}" />
		{translate key="reader.openAccess"}&nbsp;
		<img src="{$baseUrl}/lib/pkp/templates/images/icons/fulltext_restricted_medium.gif" alt="{translate key="article.accessLogoRestricted.altText"}" />
		{if $purchaseArticleEnabled}
			{translate key="reader.subscriptionOrFeeAccess"}
		{else}
			{translate key="reader.subscriptionAccess"}
		{/if}
	</div>
{/if}
{if !$showToc && $issue}
	{if $issueId}
		{url|assign:"currentUrl" page="issue" op="view" path=$issueId|to_array:"showToc"}
	{else}
		{url|assign:"currentUrl" page="issue" op="current" path="showToc"}
	{/if}
	<ul class="menu">
		<li><a href="{$currentUrl}">{translate key="issue.toc"}</a></li>
	</ul>
	<br />
	{if $coverPagePath}<div id="issueCoverImage"><a href="{$currentUrl}"><img src="{$coverPagePath|escape}{$issue->getFileName($locale)|escape}"{if $coverPageAltText != ''} alt="{$coverPageAltText|escape}"{else} alt="{translate key="issue.coverPage.altText"}"{/if}{if $width} width="{$width|escape}"{/if}{if $height} height="{$height|escape}"{/if}/></a></div>{/if}
	<div id="issueCoverDescription">{$issue->getLocalizedCoverPageDescription()|strip_unsafe_html|nl2br}</div>
{elseif $issue}
	<div id="issueDescription">{$issue->getLocalizedDescription()|strip_unsafe_html|nl2br}</div>
	{if $issueGalleys}
		<h3>{translate key="issue.fullIssue"}</h3>
		{if (!$subscriptionRequired || $issue->getAccessStatus() == $smarty.const.ISSUE_ACCESS_OPEN || $subscribedUser || $subscribedDomain || ($subscriptionExpiryPartial && $issueExpiryPartial))}
			{assign var=hasAccess value=1}
		{else}
			{assign var=hasAccess value=0}
		{/if}
		<table class="tocArticle" width="100%">
		<tr valign="top">
			<td class="tocTitle">{translate key="issue.viewIssueDescription"}</td>
			<td class="tocGalleys">
			{if $hasAccess || ($subscriptionRequired && $showGalleyLinks)}
				{foreach from=$issueGalleys item=issueGalley}
					{if $issueGalley->isPdfGalley()}
						<a href="{url page="issue" op="viewIssue" path=$issue->getBestIssueId()|to_array:$issueGalley->getBestGalleyId($currentJournal)}" class="file">{$issueGalley->getGalleyLabel()|escape}</a>
					{else}
						<a href="{url page="issue" op="viewDownloadInterstitial" path=$issue->getBestIssueId()|to_array:$issueGalley->getBestGalleyId($currentJournal)}" class="file">{$issueGalley->getGalleyLabel()|escape}</a>
					{/if}
					{if $subscriptionRequired && $showGalleyLinks && $restrictOnlyPdf}
						{if $issue->getAccessStatus() == $smarty.const.ISSUE_ACCESS_OPEN || !$issueGalley->isPdfGalley()}
							<img class="accessLogo" src="{$baseUrl}/lib/pkp/templates/images/icons/fulltext_open_medium.gif" alt="{translate key="article.accessLogoOpen.altText"}" />
						{else}
							<img class="accessLogo" src="{$baseUrl}/lib/pkp/templates/images/icons/fulltext_restricted_medium.gif" alt="{translate key="article.accessLogoRestricted.altText"}" />
						{/if}
					{/if}
				{/foreach}
				{if $subscriptionRequired && $showGalleyLinks && !$restrictOnlyPdf}
					{if $issue->getAccessStatus() == $smarty.const.ISSUE_ACCESS_OPEN}
						<img class="accessLogo" src="{$baseUrl}/lib/pkp/templates/images/icons/fulltext_open_medium.gif" alt="{translate key="article.accessLogoOpen.altText"}" />
					{else}
						<img class="accessLogo" src="{$baseUrl}/lib/pkp/templates/images/icons/fulltext_restricted_medium.gif" alt="{translate key="article.accessLogoRestricted.altText"}" />
					{/if}
				{/if}
			{/if}
			</td>
		</tr>
		</table>
		<br />
	{/if}
	<h3>{translate key="issue.toc"}</h3>
	{include file="issue/issue.tpl"}
{else}
	{translate key="current.noCurrentIssueDesc"}
{/if}
