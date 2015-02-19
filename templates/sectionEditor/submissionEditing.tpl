{**
 * templates/sectionEditor/submissionEditing.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Submission editing.
 *
 *}
{strip}
{translate|assign:"pageTitleTranslated" key="submission.page.editing" id=$submission->getId()}
{assign var="pageCrumbTitle" value="submission.editing"}
{include file="common/header.tpl"}
{/strip}

<ul class="menu">
	<li><a href="{url op="submission" path=$submission->getId()}">{translate key="submission.summary"}</a></li>
	{if $canReview}<li><a href="{url op="submissionReview" path=$submission->getId()}">{translate key="submission.review"}</a></li>{/if}
	<li class="current"><a href="{url op="submissionEditing" path=$submission->getId()}">{translate key="submission.editing"}</a></li>
	<li><a href="{url op="submissionHistory" path=$submission->getId()}">{translate key="submission.history"}</a></li>
	<li><a href="{url op="submissionCitations" path=$submission->getId()}">{translate key="submission.citations"}</a></li>
</ul>

{include file="sectionEditor/submission/summary.tpl"}

<div class="separator"></div>

{include file="sectionEditor/submission/copyedit.tpl"}

<div class="separator"></div>

{include file="sectionEditor/submission/scheduling.tpl"}

<div class="separator"></div>

{include file="sectionEditor/submission/layout.tpl"}

<div class="separator"></div>

{include file="sectionEditor/submission/proofread.tpl"}

{include file="common/footer.tpl"}

