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
{include file="common/header.tpl"}

<div class="blockTitle">{translate key="editor.submissionReview"}</div>
<div class="block">
	<ul>
		<li><a href="{$pageUrl}/reviewer/assignments">{translate key="reviewer.pendingReviews"}</a></li>
		<li><a href="{$pageUrl}/reviewer/assignments/completed">{translate key="reviewer.completedReviews"}</a></li>
	</ul>
</div>

{include file="common/footer.tpl"}
