{**
 * submission.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Author's submission summary.
 *
 * $Id$
 *}

{assign_translate var="pageTitleTranslated" key="submission.page.summary" id=$submission->getArticleId()}
{assign var="pageCrumbTitle" value="submission.summary"}
{assign var="pageId" value="author.submission"}
{include file="common/header.tpl"}

<ul class="menu">
	<li class="current"><a href="{$requestPageUrl}/submission/{$submission->getArticleId()}">{translate key="submission.summary"}</a></li>
	<li><a href="{$requestPageUrl}/submissionReview/{$submission->getArticleId()}">{translate key="submission.review"}</a></li>
	<li><a href="{$requestPageUrl}/submissionEditing/{$submission->getArticleId()}">{translate key="submission.editing"}</a></li>
</ul>

{include file="author/submission/management.tpl"}

<div class="separator"></div>

{include file="author/submission/status.tpl"}

<div class="separator"></div>

{include file="author/submission/metadata.tpl"}

<div class="separator"></div>

{include file="common/footer.tpl"}
