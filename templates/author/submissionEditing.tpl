{**
 * submissionEditing.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Author's submission editing.
 *
 * $Id$
 *}

{assign_translate var="pageTitleTranslated" key="submission.page.editing" id=$submission->getArticleId()}
{assign var="pageCrumbTitle" value="submission.editing"}
{include file="common/header.tpl"}

<ul class="menu">
	<li><a href="{$requestPageUrl}/submission/{$submission->getArticleId()}">{translate key="submission.summary"}</a></li>
	<li><a href="{$requestPageUrl}/submissionReview/{$submission->getArticleId()}">{translate key="submission.review"}</a></li>
	<li class="current"><a href="{$requestPageUrl}/submissionEditing/{$submission->getArticleId()}">{translate key="submission.editing"}</a></li>
</ul>

{include file="author/submission/summary.tpl"}

<div class="separator"></div>

{include file="author/submission/copyedit.tpl"}

<div class="separator"></div>

{include file="author/submission/layout.tpl"}

<div class="separator"></div>

{include file="author/submission/proofread.tpl"}

{include file="common/footer.tpl"}
