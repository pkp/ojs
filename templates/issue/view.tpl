{**
 * view.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * View issue -- This displays the issue TOC or title page, as appropriate,
 * *without* header or footer HTML (see viewPage.tpl)
 *
 * $Id$
 *}
{if $subscriptionRequired && $showGalleyLinks && $showToc}
	<img src="{$baseUrl}/templates/images/icons/fulltext_open_medium.png">
	{translate key="reader.openAccess"}&nbsp;
	<img src="{$baseUrl}/templates/images/icons/fulltext_restricted_medium.png">
	{if $purchaseArticleEnabled}
		{translate key="reader.subscriptionOrFeeAccess"}
	{else}
		{translate key="reader.subscriptionAccess"}
	{/if}
	<br />
	<br />
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
	{if $coverPagePath}<div id="issueCoverImage"><a href="{$currentUrl}"><img src="{$coverPagePath|escape}"{if $coverPageAltText != ''} alt="{$coverPageAltText|escape}"{else} alt="{translate key="issue.coverPage.altText"}"{/if}{if $width} width="{$width|escape}"{/if}{if $height} height="{$height|escape}"{/if}/></a></div>{/if}
	<div id="issueCoverDescription">{$issue->getIssueCoverPageDescription()|strip_unsafe_html|nl2br}</div>
{elseif $issue}
	<div id="issueDescription">{$issue->getIssueDescription()|strip_unsafe_html|nl2br}</div>
	<h3>{translate key="issue.toc"}</h3>
	{include file="issue/issue.tpl"}
{else}
	{translate key="current.noCurrentIssueDesc"}
{/if}

