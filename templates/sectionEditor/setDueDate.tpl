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

{assign var="pageTitle" value="submission.dueDate"}
{include file="common/header.tpl"}

<div class="subTitle">{translate key="editor.article.designateDueDate"}</div>

<br />

{translate key="editor.article.designateDueDateDescription"}

<br /><br />

<form method="post" action="{$requestPageUrl}/setDueDate/{$articleId}/{$reviewId}">
<div class="tableContainer">
<table width="100%">
<tr class="heading">
	<td>{translate key="editor.article.designateDueDate"}</td>
</tr>
<tr>
	<td>
		<table class="plain" width="100%">
			<tr>
				<td width="20%">{translate key="editor.article.todaysDate"}</td>
				<td width="80%">{$todaysDate}</td>
			</tr>
			<tr>
				<td valign="top">{translate key="editor.article.requestedByDate"}</td>
				<td>
					<input type="text" size="11" maxlength="10" name="dueDate" value="{if $dueDate}{$dueDate|date_format:"%Y-%m-%d"}{/if}" />
					<div>{translate key="editor.article.dueDateFormat"}</div>
				</td>
			</tr>
			<tr>
				<td></td>
				<td>{translate key="common.or"}</td>
			</tr>
			<tr>
				<td valign="top">{translate key="editor.article.numberOfWeeks"}</td>
				<td><input type="text" name="numWeeks" value="{if not $dueDate}2{/if}" size="3" maxlength="2" /></td>
			</tr>
			<tr>
				<td></td>
				<td><input type="submit" value="{translate key="form.submit"}" class="button" /></td>
			</tr>
		</table>
	</td>
</tr>
</table>
</form>

{include file="common/footer.tpl"}
