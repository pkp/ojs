{**
 * submissions.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show list of a layout editor's assigned submissions.
 * FIXME Missing some field, should support active/completed view
 *
 * $Id$
 *}

{if $showActive}
{assign var="pageTitle" value="layoutEditor.activeEditorialAssignments"}
{else}
{assign var="pageTitle" value="layoutEditor.completedEditorialAssignments"}
{/if}
{include file="common/header.tpl"}

<table width="100%">
<tr class="heading">
	<td>{translate key="common.id"}</td>
	<td><nobr>{translate key="common.dateSubmitted"}</nobr></td>
	<td width="60%">{translate key="common.title"}</td>
	<td><nobr>{translate key="common.dateRequested"}</nobr></td>
	<td><nobr>{translate key="common.dateCompleted"}</nobr></td>
</tr>
{foreach from=$submissions item=submission}
{assign var=layoutAssignment value=$submission->getLayoutAssignment()}
<tr class="{cycle values="row,rowAlt"}">
	<td><a href="{$requestPageUrl}/submission/{$submission->getArticleId()}">{$submission->getArticleID()}</a></td>
	<td>{if $submission->getDateSubmitted()}{$submission->getDateSubmitted()|date_format:$dateFormatShort}{else}-{/if}</td>
	<td><a href="{$requestPageUrl}/submission/{$submission->getArticleId()}">{$submission->getArticleTitle()}</a></td>
	<td>{if $layoutAssignment->getDateNotified()}{$layoutAssignment->getDateNotified()|date_format:$dateFormatShort}{else}-{/if}</td>
	<td>{if $layoutAssignment->getDateCompleted()}{$layoutAssignment->getDateCompleted()|date_format:$dateFormatShort}{else}-{/if}</td>
</tr>
{foreachelse}
<tr>
<td colspan="5" class="noResults">{translate key="layoutEditor.noActiveAssignments"}</td>
</tr>
{/foreach}
</table>

{include file="common/footer.tpl"}
