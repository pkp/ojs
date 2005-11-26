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
{include file="common/header.tpl"}

<h3>{translate key="article.submissions"}</h3>

<ul class="plain">
	<li>&#187; <a href="{url op="submissions" path="submissionsUnassigned"}">{translate key="common.queue.short.submissionsUnassigned"}</a>&nbsp;({if $submissionsCount[0]}<strong>{$submissionsCount[0]}</strong>{else}0{/if})</li>
	<li>&#187; <a href="{url op="submissions" path="submissionsInReview"}">{translate key="common.queue.short.submissionsInReview"}</a>&nbsp;({if $submissionsCount[1]}<strong>{$submissionsCount[1]}</strong>{else}0{/if})</li>
	<li>&#187; <a href="{url op="submissions" path="submissionsInEditing"}">{translate key="common.queue.short.submissionsInEditing"}</a>&nbsp;({if $submissionsCount[2]}<strong>{$submissionsCount[2]}</strong>{else}0{/if})</li>
	<li>&#187; <a href="{url op="submissions" path="submissionsArchives"}">{translate key="common.queue.short.submissionsArchives"}</a></li>
	{call_hook name="Templates::Editor::Index::Submissions"}
</ul>


<h3>{translate key="editor.navigation.issues"}</h3>

<ul class="plain">
	<li>&#187; <a href="{url op="createIssue"}">{translate key="editor.navigation.createIssue"}</a></li>
	<li>&#187; <a href="{url op="schedulingQueue"}">{translate key="common.queue.long.submissionsInScheduling"}</a>&nbsp;({if $submissionsCount[3]}<strong>{$submissionsCount[3]}</strong>{else}0{/if})</li>
	<li>&#187; <a href="{url op="notifyUsers"}">{translate key="editor.notifyUsers"}</a></li>
	<li>&#187; <a href="{url op="futureIssues"}">{translate key="editor.navigation.futureIssues"}</a></li>
	<li>&#187; <a href="{url op="backIssues"}">{translate key="editor.navigation.issueArchive"}</a></li>
	{call_hook name="Templates::Editor::Index::Issues"}
</ul>

{include file="common/footer.tpl"}
