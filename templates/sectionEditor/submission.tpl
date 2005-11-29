{**
 * submission.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Submission summary.
 *
 * $Id$
 *}

{translate|assign:"pageTitleTranslated" key="submission.page.summary" id=$submission->getArticleId()}
{assign var="pageCrumbTitle" value="submission.summary"}
{include file="common/header.tpl"}

<ul class="menu">
	<li class="current"><a href="{url op="submission" path=$submission->getArticleId()}">{translate key="submission.summary"}</a></li>
	<li><a href="{url op="submissionReview" path=$submission->getArticleId()}">{translate key="submission.review"}</a></li>
	<li><a href="{url op="submissionEditing" path=$submission->getArticleId()}">{translate key="submission.editing"}</a></li>
	<li><a href="{url op="submissionHistory" path=$submission->getArticleId()}">{translate key="submission.history"}</a></li>
</ul>

{include file="sectionEditor/submission/management.tpl"}

<div class="separator"></div>

{include file="sectionEditor/submission/status.tpl"}

<div class="separator"></div>

{include file="sectionEditor/submission/metadata.tpl"}

{include file="common/footer.tpl"}
