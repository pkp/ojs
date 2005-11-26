{**
 * submissionEditing.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Submission editing.
 *
 * $Id$
 *}

{assign_translate var="pageTitleTranslated" key="submission.page.editing" id=$submission->getArticleId()}
{assign var="pageCrumbTitle" value="submission.editing"}
{include file="common/header.tpl"}

<ul class="menu">
	<li><a href="{url op="submission" path=$submission->getArticleId()}">{translate key="submission.summary"}</a></li>
	<li><a href="{url op="submissionReview" path=$submission->getArticleId()}">{translate key="submission.review"}</a></li>
	<li class="current"><a href="{url op="submissionEditing" path=$submission->getArticleId()}">{translate key="submission.editing"}</a></li>
	<li><a href="{url op="submissionHistory" path=$submission->getArticleId()}">{translate key="submission.history"}</a></li>
</ul>

{include file="sectionEditor/submission/summary.tpl"}

<div class="separator"></div>

{include file="sectionEditor/submission/copyedit.tpl"}

<div class="separator"></div>

{include file="sectionEditor/submission/layout.tpl"}

<div class="separator"></div>

{include file="sectionEditor/submission/proofread.tpl"}

{include file="common/footer.tpl"}
