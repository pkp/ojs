{**
 * @file plugins/generic/objectsForReview/templates/editor/objectsForReviewAssignmentsList.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display the list of objects for review assignments for editor management.
 *
 *}
<form name="filterForm" action="#">
<ul class="filter">
	<li>{translate key="editor.submissions.assignedTo"}: <select name="filterEditor" onchange="location.href='{url|escape path=$returnPage searchField=$searchField searchMatch=$searchMatch search=$search filterEditor="EDITOR" filterType="TYPE" sort=$sort sortDirection=$sortDirection escape=false}'.replace('EDITOR', this.options[this.selectedIndex].value).replace('TYPE', document.forms.filterForm.elements.filterType.value)" size="1" class="selectMenu">{html_options options=$editorOptions selected=$filterEditor}</select></li>
	<li>{translate key="plugins.generic.objectsForReview.editor.objectType"}: <select name="filterType" onchange="location.href='{url|escape path=$returnPage searchField=$searchField searchMatch=$searchMatch search=$search filterEditor="EDITOR" filterType="TYPE" sort=$sort sortDirection=$sortDirection escape=false}'.replace('TYPE', this.options[this.selectedIndex].value).replace('EDITOR', document.forms.filterForm.elements.filterEditor.value)" size="1" class="selectMenu">{html_options options=$filterTypeOptions selected=$filterType}</select></li>
</ul>
</form>

<form method="get" action="{url op="objectsForReview" path=$returnPage}">
	<input type="hidden" name="filterEditor" value="{$filterEditor|escape}" />
	<input type="hidden" name="filterType" value="{$filterType|escape}" />
	<input type="hidden" name="sort" value="{$sort|escape}" />
	<input type="hidden" name="sortDirection" value="{$sortDirection|escape}" />
	<select name="searchField" size="1" class="selectMenu">
		{html_options_translate options=$fieldOptions selected=$searchField}
	</select>
	<select name="searchMatch" size="1" class="selectMenu">
		<option value="contains"{if $searchMatch == 'contains'} selected="selected"{/if}>{translate key="form.contains"}</option>
		<option value="is"{if $searchMatch == 'is'} selected="selected"{/if}>{translate key="form.is"}</option>
	</select>
	<input type="text" size="30" name="search" class="textField" value="{$search|escape}" />
	<input type="submit" value="{translate key="common.search"}" class="button" />
</form>

<br />

{if $mode == $smarty.const.OFR_MODE_FULL}
	{assign var=colspan value="6"}
	{assign var=colspanPage value="3"}
{else}
	{assign var=colspan value="4"}
	{assign var=colspanPage value="2"}
{/if}
<table width="100%" class="listing">
	<tr>
		<td colspan="{$colspan}" class="headseparator">&nbsp;</td>
	</tr>
{if $mode == $smarty.const.OFR_MODE_FULL}
	<tr class="heading" valign="bottom">
		<td width="30%">{sort_heading key="plugins.generic.objectsForReview.objectForReviewAssignments.title" sort="title"}</td>
		<td width="7%">{sort_heading key="plugins.generic.objectsForReview.objectForReviewAssignments.status" sort="status"}</td>
		<td width="25%">{sort_heading key="plugins.generic.objectsForReview.objectForReviewAssignments.objectReviewer" sort="reviewer"}</td>
		<td width="15%">{sort_heading key="plugins.generic.objectsForReview.objectForReviewAssignments.dueDate" sort="due"}</td>
		<td width="18%" align="right">{sort_heading key="plugins.generic.objectsForReview.objectForReviewAssignments.submission" sort="submission"}</td>
		<td width="5%" align="right">{sort_heading key="plugins.generic.objectsForReview.objectForReviewAssignments.editor" sort="editor"}</td>
	</tr>
{else}
	<tr class="heading" valign="bottom">
		<td width="70%">{sort_heading key="plugins.generic.objectsForReview.objectForReviewAssignments.title" sort="title"}</td>
		<td width="7%">{sort_heading key="plugins.generic.objectsForReview.objectForReviewAssignments.status" sort="status"}</td>
		<td width="18%" align="right">{sort_heading key="plugins.generic.objectsForReview.objectForReviewAssignments.submission" sort="submission"}</td>
		<td width="5%" align="right">{sort_heading key="plugins.generic.objectsForReview.objectForReviewAssignments.editor" sort="editor"}</td>
{/if}
	<tr>
		<td colspan="{$colspan}" class="headseparator">&nbsp;</td>
	</tr>
{iterate from=objectForReviewAssignments item=objectForReviewAssignment}
{assign var=objectForReview value=$objectForReviewAssignment->getObjectForReview()}
	<tr {if $objectForReviewAssignment->isLate() && $objectForReviewAssignment->getStatus() != $smarty.const.OFR_STATUS_SUBMITTED}class="highlight"{/if} valign="top">
		<td><a href="{url op="editObjectForReviewAssignment" path=$objectForReviewAssignment->getId() objectId=$objectForReviewAssignment->getObjectId() returnPage=$returnPage}" class="action">{$objectForReview->getTitle()|escape|truncate:40:"..."}</a></td>
		{assign var=status value=$objectForReviewAssignment->getStatus()}
		{assign var=statusString value=$objectForReviewAssignment->getStatusString()}
		{assign var=userId value=$objectForReviewAssignment->getUserId()}
		<td>{translate key=$statusString}</td>
		{if $mode == $smarty.const.OFR_MODE_FULL}
			{if $userId}
				{assign var=author value=$objectForReviewAssignment->getUser()}
				{assign var=emailString value=$author->getFullName()|concat:" <":$author->getEmail():">"}
				{url|assign:"url" page="user" op="email" to=$emailString|to_array redirectUrl=$currentUrl}
				<td>{$author->getFullName()|escape}&nbsp;{icon name="mail" url=$url}
			{else}
				<td>
			{/if}
			{if $status == $smarty.const.OFR_STATUS_REQUESTED}
				<br />
				<a href="{url op="acceptObjectForReviewAuthor" path=$objectForReviewAssignment->getId() returnPage=$returnPage}" class="action">{translate key="plugins.generic.objectsForReview.editor.acceptObjectReviewer"}</a>&nbsp;|&nbsp;<a href="{url op="denyObjectForReviewAuthor" path=$objectForReviewAssignment->getId() returnPage=$returnPage}" class="action">{translate key="plugins.generic.objectsForReview.editor.denyObjectReviewer"}</a></td>
			{elseif $status == $smarty.const.OFR_STATUS_ASSIGNED}
				<br />
				{if $objectForReview->getCopy()}
					<a href="{url op="notifyObjectForReviewMailed" path=$objectForReviewAssignment->getId() returnPage=$returnPage}" class="action">{translate key="plugins.generic.objectsForReview.editor.notifyObjectMailed"}</a>&nbsp;|
				{/if}
				<a href="{url op="removeObjectForReviewAssignment" path=$objectForReviewAssignment->getId() returnPage=$returnPage}" class="action" onclick="return confirm('{translate|escape:"jsparam" key="plugins.generic.objectsForReview.editor.confirmRemoveObjectReviewer"}')">{translate key="plugins.generic.objectsForReview.editor.removeObjectReviewer"}</a></td>
			{elseif $status == $smarty.const.OFR_STATUS_MAILED}
				<br />
				<a href="{url op="removeObjectForReviewAssignment" path=$objectForReviewAssignment->getId() returnPage=$returnPage}" class="action" onclick="return confirm('{translate|escape:"jsparam" key="plugins.generic.objectsForReview.editor.confirmRemoveObjectReviewer"}')">{translate key="plugins.generic.objectsForReview.editor.removeObjectReviewer"}</a></td>
			{elseif $userId && $status == $smarty.const.OFR_STATUS_SUBMITTED}
				<br />
				<a href="{url op="removeObjectForReviewAssignment" path=$objectForReviewAssignment->getId() returnPage=$returnPage}" class="action" onclick="return confirm('{translate|escape:"jsparam" key="plugins.generic.objectsForReview.editor.confirmRemoveObjectReviewer"}')">{translate key="plugins.generic.objectsForReview.editor.removeObjectReviewer"}</a></td>
			{else}
				&nbsp;</td>
			{/if}
			<td>{$objectForReviewAssignment->getDateDue()|date_format:$dateFormatTrunc}</td>
		{/if}
		<td align="right">
		{assign var=submissionId value=$objectForReviewAssignment->getSubmissionId()}
		{if $submissionId}
			{translate key="common.id"}: {$submissionId|escape}
			<br />
			<a href="{url page="editor" op="submission" path=$submissionId}" class="action">{translate key="plugins.generic.objectsForReview.editor.edit"}</a>&nbsp;|&nbsp;
		{/if}
			<a href="{url op="selectObjectForReviewSubmission" path=$objectForReviewAssignment->getId() objectId=$objectForReviewAssignment->getObjectId() returnPage=$returnPage}" class="action">{translate key="plugins.generic.objectsForReview.editor.select"}</a>
		</td>
		<td align="right">{$objectForReview->getEditorInitials()|escape}</td>
	</tr>
	<tr>
		<td colspan="{$colspan}" class="{if $objectForReviewAssignments->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $objectForReviewAssignments->wasEmpty() and $search != ""}
	<tr>
		<td colspan="{$colspan}" class="nodata">{translate key="plugins.generic.objectsForReview.search.noResults"}</td>
	</tr>
	<tr>
		<td colspan="{$colspan}" class="endseparator">&nbsp;</td>
	</tr>
{elseif $objectForReviewAssignments->wasEmpty()}
	<tr>
		<td colspan="{$colspan}" class="nodata">{translate key="plugins.generic.objectsForReview.objectForReviewAssignments.noneCreated"}</td>
	</tr>
	<tr>
		<td colspan="{$colspan}" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td colspan="{$colspanPage}" align="left">{page_info iterator=$objectForReviewAssignments}</td>
		<td colspan="{$colspanPage}" align="right">{page_links anchor="objectForReviewAssignments" name="objectForReviewAssignments" iterator=$objectForReviewAssignments sort=$sort sortDirection=$sortDirection filterEditor=$filterEditor filterType=$filterType searchField=$searchField searchMatch=$searchMatch search=$search}</td>
	</tr>
{/if}
</table>
