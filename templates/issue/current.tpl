{**
 * current.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Current.
 *
 * $Id$
 *}

{assign var="pageTitleTranslated" value=$issueTitle}
{assign var="pageCrumbTitleTranslated" value=$issueCrumbTitle}
{assign var="currentUrl" value="$pageUrl/issue/current"}
{include file="common/header.tpl"}

{if !$showToc && $issue}
	<ul class="menu">
		<li><a href="{$requestPageUrl}/current/showToc">{translate key="issue.toc"}</a></li>
	</ul>
	<br />
	<div><a href="{$requestPageUrl}/current/showToc"><img src="{$coverPagePath}" width="" height="" border="0" alt="" /></a></div>		
	<div>{$issue->getCoverPageDescription()}</div>
{else}
	{include file="issue/issue.tpl"}
{/if}

{include file="common/footer.tpl"}
