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

{assign var="pageTitle" value="editor.home"}
{assign var="pageCrumbTitle" value="user.role.editor"}
{assign var="pageId" value="editor.index"}
{include file="common/header.tpl"}

<h3>{translate key="editor.navigation.submissions"}</h3>
<ul>
	<li><a href="{$pageUrl}/editor/submissions/submissionsUnassigned">{translate key="editor.navigation.unassigned"}</a>&nbsp;({if $submissionsCount[0]}<strong>{$submissionsCount[0]}</strong>{else}0{/if})</li>
	<li><a href="{$pageUrl}/editor/submissions/submissionsInReview">{translate key="editor.navigation.submissionsInReview"}</a>&nbsp;({if $submissionsCount[1]}<strong>{$submissionsCount[1]}</strong>{else}0{/if})</li>
	<li><a href="{$pageUrl}/editor/submissions/submissionsInEditing">{translate key="editor.navigation.submissionsInEditing"}</a>&nbsp;({if $submissionsCount[2]}<strong>{$submissionsCount[2]}</strong>{else}0{/if})</li>
	<li><a href="{$pageUrl}/editor/submissions/submissionsArchives">{translate key="navigation.archives"}</a></li>
</ul>

<h3>{translate key="editor.navigation.issues"}</h3>
<ul>
	<li><a href="{$pageUrl}/editor/createIssue">{translate key="editor.navigation.createIssue"}</a></li>
	<li><a href="{$pageUrl}/editor/schedulingQueue">{translate key="editor.navigation.submissionsInScheduling"}</a>&nbsp;({if $submissionsCount[3]}<strong>{$submissionsCount[3]}</strong>{else}0{/if})</li>
	<li><a href="{$pageUrl}/editor/issueToc">{translate key="editor.navigation.liveIssues"}</a></li>
	<li><a href="{$pageUrl}/editor/backIssues">{translate key="editor.navigation.issueArchive"}</a></li>
</ul>

{include file="common/footer.tpl"}
