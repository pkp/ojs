{**
 * submissions.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Proofreader submissions.
 *
 * $Id$
 *}
{if $active}
{assign var="pageTitle" value="proofreader.activeAssignments"}
{else}
{assign var="pageTitle" value="proofreader.completedAssignments"}
{/if}
{assign var="pageId" value="proofreader.submissions"}
{include file="common/header.tpl"}

<table width="100%">
<tr class="heading">
	<td>{translate key="common.id"}</td>
	<td><nobr>{translate key="common.dateSubmitted"}</nobr></td>
	<td>{translate key="common.title"}</td>
	<td><nobr>{translate key="common.dateRequested"}</nobr></td>
	<td><nobr>{translate key="common.dateCompleted"}</nobr></td>
</tr>
{foreach from=$submissions item=submission}
{assign var="proofAssignment" value=$submission->getProofAssignment()}
<tr class="{cycle values="row,rowAlt"}">
	<td><a href="{$pageUrl}/proofreader/submission/{$proofAssignment->getArticleId()}">{$proofAssignment->getArticleID()}</a></td>
	<td>{if $submission->getDateSubmitted()}{$submission->getDateSubmitted()|date_format:$dateFormatShort}{else}-{/if}</td>
	<td><a href="{$pageUrl}/proofreader/submission/{$proofAssignment->getArticleId()}">{$submission->getArticleTitle()}</a></td>
	<td>{if $proofAssignment->getDateProofreaderNotified()}{$proofAssignment->getDateProofreaderNotified()|date_format:$dateFormatShort}{else}-{/if}</td>
	<td>{if $proofAssignment->getDateProofreaderCompleted()}{$proofAssignment->getDateProofreaderCompleted()|date_format:$dateFormatShort}{else}-{/if}</td>
</tr>
{foreachelse}
<tr>
<td colspan="5" class="noResults">{translate key="proofreader.noProofreadingAssignments"}</td>
</tr>
{/foreach}
</table>

{include file="common/footer.tpl"}
