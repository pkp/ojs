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
{url|assign:"currentUrl" op="current"}
{include file="common/header.tpl"}

{if !$showToc && $issue}
	<ul class="menu">
		<li><a href="{url op="current" path="showToc"}">{translate key="issue.toc"}</a></li>
	</ul>
	<br />
	{if $coverPagePath}<div><a href="{url op="current" path="showToc"}"><img src="{$coverPagePath}" border="0" alt=""{if $height} height="{$height}"{/if}{if $width} width="{$width}"{/if} /></a></div>{/if}
	<div>{$issue->getCoverPageDescription()|escape|nl2br}</div>
{elseif $issue}
	{if $issue}<h3>{translate key="issue.toc"}</h3>{/if}
	{include file="issue/issue.tpl"}
{else}
	{translate key="current.noCurrentIssueDesc"}
{/if}

{include file="common/footer.tpl"}
