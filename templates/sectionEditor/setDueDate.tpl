{**
 * setDueDate.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form to set the due date for a review.
 *
 * $Id$
 *}

{assign var="pageTitle" value="submission.submission"}
{include file="common/header.tpl"}

<div class="subTitle">{translate key="submission.designateDueDate"}</div>

<br />

{translate key="submission.designateDueDateDescription"}

<br /><br />

<form method="post" action="{$pageUrl}/editor/setDueDate/{$articleId}/{$reviewId}">
<div class="formSectionTitle">{translate key="submission.submission"}</div>
<div class="formSection">
<table class="form" width="100%">
	<tr>
		<td class="formLabel">{translate key="submission.todaysDate"}</td>
		<td>{$todaysDate}</td>
	</tr>
	<tr>
		<td class="formLabel">{translate key="submission.requestedByDate"}</td>
		<td class="formField">
			<input type="text" name="dueDate" value="{if $dueDate}{$dueDate|date_format:"%Y-%m-%d"}{/if}">
			<div>{translate key="submission.dueDateFormat"}</div>
		</td>
	</tr>
	<tr>
		<td></td>
		<td>{translate key="common.or"}</td>
	</tr>
	<tr>
		<td class="formLabel">{translate key="submission.numberOfWeeks"}</td>
		<td class="formField"><input type="text" name="numWeeks" value="{if not $dueDate}2{/if}" size="2"></td>
	</tr>
	<tr>
		<td></td>
		<td><input type="submit" value="{translate key="form.submit"}"></td>
	</tr>
</table>
</form>

{include file="common/footer.tpl"}
