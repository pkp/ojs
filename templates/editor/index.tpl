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

{assign var="pageTitle" value="editor.editorAdministration"}
{assign var="currentUrl" value="$pageUrl/editor"}
{assign var="pageId" value="editor.index"}
{include file="common/header.tpl"}

<div class="blockTitle">{translate key="editor.submissions"}</div>
<div class="block">
	<ul>
		<li><a href="{$pageUrl}/editor/submissionQueue">{translate key="editor.submissions.inReview"}</a></li>
		<li><a href="{$pageUrl}/editor/submissionQueue">{translate key="editor.submissions.inEditing"}</a></li>
		<li><a href="{$pageUrl}/editor/schedulingQueue">{translate key="editor.submissions.schedule"}</a></li>
	</ul>
</div>

<br />

<div class="blockTitle">{translate key="editor.issues"}</div>
<div class="block">
	<ul>
		<li><a href="{$pageUrl}/editor/issueManagement">{translate key="editor.issues.liveIssues"}</a></li>
		<li><a href="{$pageUrl}/editor/createIssue">{translate key="editor.issues.createIssue"}</a></li>
	</ul>
</div>

<br />

<div class="blockTitle">{translate key="editor.archives"}</div>
<div class="block">
	<ul>
		<li><a href="{$pageUrl}/editor/submissionArchive">{translate key="editor.submissions"}</a></li>
		<li><a href="{$pageUrl}/editor/backIssues">{translate key="editor.issues.backIssues"}</a></li>
	</ul>
</div>

{include file="common/footer.tpl"}
