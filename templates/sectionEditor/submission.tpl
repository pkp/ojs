{**
 * submission.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Submission summary.
 *
 * $Id$
 *}
{strip}
{translate|assign:"pageTitleTranslated" key="submission.page.summary" id=$submission->getId()}
{assign var="pageCrumbTitle" value="submission.summary"}
{include file="common/header.tpl"}
{/strip}

<ul class="menu">
	<li class="current"><a href="{url op="submission" path=$submission->getId()}">{literal}1. {/literal}{translate key="submission.summary"}</a></li>
	{if $canReview}<li><a href="{url op="submissionReview" path=$submission->getId()}">{literal}2. {/literal}{translate key="submission.review"}</a></li>{/if}
	{if $canEdit}<li><a href="{url op="submissionEditing" path=$submission->getId()}">{literal}3. {/literal}{translate key="submission.editing"}</a></li>{/if}
	&nbsp;&nbsp;{literal}|{/literal}&nbsp;&nbsp;
	<li><a href="{url op="submissionHistory" path=$submission->getId()}">{translate key="submission.history"}</a></li>
	{* 20110829 BLH display REFERENCES link only if this is enabled for the journal *}
	{if $currentJournal->getSetting('metaCitations')}
	<li><a href="{url op="submissionCitations" path=$submission->getId()}">{translate key="submission.citations"}</a></li>
	{/if}
</ul>

{include file="sectionEditor/submission/management.tpl"}

{if $authorFees}
<div class="separator"></div>

{include file="sectionEditor/submission/authorFees.tpl"}
{/if}

<div class="separator"></div>

{include file="sectionEditor/submission/editors.tpl"}

<div class="separator"></div>

{include file="sectionEditor/submission/status.tpl"}

<div class="separator"></div>

{include file="submission/metadata/metadata.tpl"}

{include file="common/footer.tpl"}

