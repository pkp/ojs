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
{assign var="pageId" value="sectionEditor.submissionEditing"}
{include file="common/header.tpl"}

<ul class="menu">
	<li><a href="{$requestPageUrl}/submission/{$submission->getArticleId()}">{translate key="submission.summary"}</li>
	<li><a href="{$requestPageUrl}/submissionReview/{$submission->getArticleId()}">{translate key="submission.review"}</li>
	<li class="current"><a href="{$requestPageUrl}/submissionEditing/{$submission->getArticleId()}">{translate key="submission.editing"}</li>
	<li><a href="{$requestPageUrl}/submissionHistory/{$submission->getArticleId()}">{translate key="submission.history"}</li>
</ul>

{include file="sectionEditor/submission/summary.tpl"}

<div class="separator"></div>

{include file="sectionEditor/submission/copyedit.tpl"}

<div class="separator"></div>

{include file="sectionEditor/submission/layout.tpl"}

<div class="separator"></div>

{include file="sectionEditor/submission/proofread.tpl"}

{include file="common/footer.tpl"}
