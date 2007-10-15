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
{if $subscriptionRequired && $showGalleyLinks}
	<img src="{$baseUrl}/templates/images/icons/fulltext_open_medium.png">
	{translate key="reader.openAccess"}&nbsp;
	<img src="{$baseUrl}/templates/images/icons/fulltext_restricted_medium.png">
	{if $payPerViewEnabled}
		{translate key="reader.subscriptionOrFeeAccess"}
	{else}
		{translate key="reader.subscriptionAccess"}
	{/if}
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
	{if $coverPagePath}<div><a href="{$currentUrl}"><img src="{$coverPagePath|escape}" style="border: 0;" alt=""{if $width} width="{$width}"{/if}{if $height} height="{$height}"{/if}/></a></div>{/if}
	<div>{$issue->getIssueCoverPageDescription()|strip_unsafe_html|nl2br}</div>
{elseif $issue}
	<div>{$issue->getIssueDescription()|strip_unsafe_html|nl2br}</div>
	<h3>{translate key="issue.toc"}</h3>
	{include file="issue/issue.tpl"}
{else}
	{translate key="current.noCurrentIssueDesc"}
{/if}

