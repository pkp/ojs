{**
 * submissionCitations.tpl
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Submission citations.
 *
 * $Id:  $
 *}
{strip}
{translate|assign:"pageTitleTranslated" key="submission.page.citations" id=$submission->getId()}
{assign var="pageCrumbTitle" value="submission.citations"}
{include file="common/header.tpl"}
{/strip}

<ul class="menu">
	<li><a href="{url op="submission" path=$submission->getId()}">{translate key="submission.summary"}</a></li>
	{if $canReview}<li><a href="{url op="submissionReview" path=$submission->getId()}">{translate key="submission.review"}</a></li>{/if}
	{if $canEdit}<li><a href="{url op="submissionEditing" path=$submission->getId()}">{translate key="submission.editing"}</a></li>{/if}
	<li><a href="{url op="submissionHistory" path=$submission->getId()}">{translate key="submission.history"}</a></li>
	{if $journalSettings.metaCitations}<li class="current"><a href="{url op="submissionCitations" path=$submission->getId()}">{translate key="submission.citations"}</a></li>{/if}
</ul>

<div id="submissionCitations">
	<h3>{translate key="submission.citations"}</h3>

	{load_div id="citationGridContainer" loadMessageId="submission.citations.form.loadMessage" url="$citationGridUrl"}
</div>

{include file="common/footer.tpl"}
