{**
 * complete.tpl
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * The submission process has been completed; notify the author.
 *
 * $Id$
 *}
{assign var="pageTitle" value="author.track"}
{include file="common/header.tpl"}

<p>{translate key="author.submit.submissionComplete" journalTitle=$journal->getJournalTitle()}</p>

{if $canExpedite}
	{url|assign:"expediteUrl" op="expediteSubmission" articleId=$articleId}
	{translate key="author.submit.expedite" expediteUrl=$expediteUrl}
{/if}
{if $paymentButtonsTemplate }
	{include file=$paymentButtonsTemplate orientation="vertical"}
{/if}

<p>&#187; <a href="{url op="track"}">{translate key="author.track"}</a></p>

{include file="common/footer.tpl"}
