{**
 * index.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Reviewer index.
 *
 * $Id$
 *}

{assign var="pageTitle" value="reviewer.journalReviewer"}
{assign var="pageId" value="reviewer.index"}
{include file="common/header.tpl"}

<div class="blockTitle">
	{translate key="editor.submissionReview"}&nbsp;
	<a href="javascript:openHelp('{get_help_id key="$pageId.submissionReview" url="true"}')"  class="icon"><img src="{$baseUrl}/templates/images/info.gif" width="16" height="17" border="0" alt="info" /></a>
</div>
<div class="block">
	<ul>
		<li><a href="{$pageUrl}/reviewer/assignments">{translate key="reviewer.pendingReviews"}</a></li>
		<li><a href="{$pageUrl}/reviewer/assignments/completed">{translate key="reviewer.completedReviews"}</a></li>
	</ul>
</div>

{include file="common/footer.tpl"}
