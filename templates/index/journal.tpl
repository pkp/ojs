{**
 * index.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Journal index page.
 *
 * $Id$
 *}

{include file="common/header.tpl"}

<div>{$journalDescription}</div>

<br />

{if $displayCurrentIssue && $issue}

	{if !$showToc}
		<div><h4>{$issue->getIssueIdentification()}&nbsp;<a href="{$requestPageUrl}/index/showToc">{translate key="issue.toc"}</a></h4></div>
		<div><a href="{$requestPageUrl}/index/showToc"><img src="{$coverPagePath}" width="" height="" border="0" alt="" /></a></div>
		<div>{$issue->getCoverPageDescription()}</div>
	{else}
		<div><h4>{translate key="issue.toc"},&nbsp;{$issue->getIssueIdentification()}</h4></div>
		{include file="issue/issue.tpl"}
	{/if}

{/if}

{if $homepageImage}
<div align="center"><img src="{$publicFilesDir}/{$homepageImage.uploadName}" width="{$homepageImage.width}" height="{$homepageImage.height}" border="0" alt="" /></div>
{/if}

<br /><br />

{$additionalHomeContent}

{include file="common/footer.tpl"}
