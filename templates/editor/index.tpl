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

<div class="blockTitle">
	{translate key="editor.submissions"}&nbsp;
	<a href="javascript:openHelp('{get_help_id key="$pageId.submissions" url="true"}')"  class="icon"><img src="{$baseUrl}/templates/images/info.gif" width="16" height="17" border="0" alt="info" /></a>
</div>
<div class="block">
	<ul>
		<li><a href="{$pageUrl}/editor/submissionQueue">{translate key="editor.submissionQueue"}</a></li>
		<li><a href="{$pageUrl}/editor/submissionArchive">{translate key="editor.submissionArchive"}</a></li>
	</ul>
</div>

<br />

<div class="blockTitle">
	{translate key="editor.publishing"}&nbsp;
	<a href="javascript:openHelp('{get_help_id key="$pageId.publishing" url="true"}')"  class="icon"><img src="{$baseUrl}/templates/images/info.gif" width="16" height="17" border="0" alt="info" /></a>
</div>
<div class="block">
	<ul>
		<li><a href="{$pageUrl}/editor/schedulingQueue">{translate key="editor.schedulingQueue"}</a></li>
		<li><a href="{$pageUrl}/editor/editIssue">{translate key="editor.currentIssue"}</a></li>
		<li><a href="{$pageUrl}/editor/issues">{translate key="editor.publishedIssues"}</a></li>
	</ul>
</div>

{include file="common/footer.tpl"}
