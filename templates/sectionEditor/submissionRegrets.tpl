{**
 * submissionRegrets.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show submission regrets/cancels/earlier rounds
 *
 *
 * $Id$
 *}

{assign_translate var="pageTitleTranslated" key="sectionEditor.regrets.title" articleId=$submission->getArticleId()}
{assign var=pageTitleTranslated value=$pageTitleTranslated|escape}
{assign var="pageCrumbTitle" value="sectionEditor.regrets.breadcrumb"}
{include file="common/header.tpl"}

<ul class="menu">
	<li><a href="{$requestPageUrl}/submission/{$submission->getArticleId()}">{translate key="submission.summary"}</a></li>
	<li><a href="{$requestPageUrl}/submissionReview/{$submission->getArticleId()}">{translate key="submission.review"}</a></li>
	<li><a href="{$requestPageUrl}/submissionEditing/{$submission->getArticleId()}">{translate key="submission.editing"}</a></li>
	<li><a href="{$requestPageUrl}/submissionHistory/{$submission->getArticleId()}">{translate key="submission.history"}</a></li>
</ul>

{include file="sectionEditor/submission/summary.tpl"}

<div class="separator"></div>

{include file="sectionEditor/submission/rounds.tpl"}

{include file="common/footer.tpl"}
