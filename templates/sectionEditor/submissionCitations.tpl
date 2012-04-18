{**
 * submissionCitations.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Submission citations.
 *}
{strip}
{translate|assign:"pageTitleTranslated" key="submission.page.citations" id=$submission->getId()}
{assign var="pageCrumbTitle" value="submission.citations"}
{include file="common/header.tpl"}
{/strip}

<ul class="menu">
	<li><a href="{url op="submission" path=$submission->getId()}">{literal}1. {/literal}{translate key="submission.summary"}</a></li>
	{if $canReview}<li><a href="{url op="submissionReview" path=$submission->getId()}">{literal}2. {/literal}{translate key="submission.review"}</a></li>{/if}
	{if $canEdit}<li><a href="{url op="submissionEditing" path=$submission->getId()}">{literal}3. {/literal}{translate key="submission.editing"}</a></li>{/if}
	&nbsp;&nbsp;{literal}|{/literal}&nbsp;&nbsp;
	<li><a href="{url op="submissionHistory" path=$submission->getId()}">{translate key="submission.history"}</a></li>
	{* 20110829 BLH display REFERENCES link only if this is enabled for the journal *}
	{if $currentJournal->getSetting('metaCitations')}
	<li class="current"><a href="{url op="submissionCitations" path=$submission->getId()}">{translate key="submission.citations"}</a></li>
	{/if}
</ul>

{include file="citation/citationEditor.tpl}

{include file="common/footer.tpl"}

