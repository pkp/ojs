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

{assign var="pageTitle" value="author.submissions"}
{include file="common/header.tpl"}

<div class="subTitle">Designate a Due Date</div>

<br />

Either enter the date by which the review should be completed or the number of days until it is due from this date. 

<br /><br />

<form method="post" action="{$pageUrl}/editor/setDueDate/{$articleId}/{$reviewId}">
<div class="formSectionTitle">Submission</div>
<div class="formSection">
<table class="form" width="100%">
	<tr>
		<td class="formLabel">Today's Date</td>
		<td></td>
	</tr>
	<tr>
		<td class="formLabel">Requested by Date</td>
		<td class="formField">
			<input type="text" name="dueDate" value="{if $dueDate}{$dueDate|date_format:"%Y-%m-%d"}{/if}">
			<div>Format: YYYY-MM-DD</div>
		</td>
	</tr>
	<tr>
		<td></td>
		<td>OR</td>
	</tr>
	<tr>
		<td class="formLabel">Number of Weeks</td>
		<td class="formField"><input type="text" name="numWeeks" value="{if not $dueDate}2{/if}" size="2"></td>
	</tr>
	<tr>
		<td></td>
		<td><input type="submit" value="Submit"></td>
	</tr>
</table>
</form>

{include file="common/footer.tpl"}
