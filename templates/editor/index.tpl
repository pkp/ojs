{**
 * index.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Editor index.
 *
 * $Id$
 *}

{assign var="pageTitle" value="editor.journalEditor"}
{assign var="pageId" value="editor.index"}
{include file="common/header.tpl"}

<div class="blockTitle">{translate key="editor.submissions"}</div>
<div class="block">
	<ul>
		<li><a href="{$pageUrl}/editor/submissionQueue">{translate key="editor.submissionQueue"}</a></li>
		<li><a href="{$pageUrl}/editor/submissionArchive">{translate key="editor.submissionArchive"}</a></li>
	</ul>
</div>

<br />

<div class="blockTitle">{translate key="editor.publishing"}</div>
<div class="block">
	<ul>
		<li><a href="{$pageUrl}/editor/schedulingQueue">{translate key="editor.schedulingQueue"}</a></li>
		<li><a href="{$pageUrl}/editor/editIssue">{translate key="editor.currentIssue"}</a></li>
		<li><a href="{$pageUrl}/editor/issues">{translate key="editor.publishedIssues"}</a></li>
	</ul>
</div>

{include file="common/footer.tpl"}
