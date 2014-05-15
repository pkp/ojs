{**
 * @file plugins/generic/objectsForReview/templates/author/objectsForReviewAssignmentsList.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display the list of objects for review assigned to this author.
 *
 *}
<br />

<table width="100%" class="listing">
	<tr>
		<td colspan="6" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="35%">{sort_heading key="plugins.generic.objectsForReview.objectForReviewAssignments.title" sort="title"}</td>
		<td width="15%">{sort_heading key="plugins.generic.objectsForReview.objectsForReview.objectType" sort="type"}</td>
		<td width="15%">{sort_heading key="plugins.generic.objectsForReview.objectForReviewAssignments.status" sort="status"}</td>
		<td width="15%">{sort_heading key="plugins.generic.objectsForReview.objectForReviewAssignments.editor" sort="editor"}</td>
		<td width="10%">{sort_heading key="plugins.generic.objectsForReview.objectForReviewAssignments.dueDate" sort="due"}</td>
		<td width="10%">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td colspan="6" class="headseparator">&nbsp;</td>
	</tr>
{iterate from=objectForReviewAssignments item=objectForReviewAssignment}
	{assign var=objectForReview value=$objectForReviewAssignment->getObjectForReview()}
	{assign var=reviewObjectType value=$objectForReview->getReviewObjectType()}
	{assign var=status value=$objectForReviewAssignment->getStatus()}
	{assign var=statusString value=$objectForReviewAssignment->getStatusString()}
	<tr valign="top">
		<td>{$objectForReview->getTitle()|escape|truncate:40:"..."}</td>
		<td>{$reviewObjectType->getLocalizedName()|escape}</td>
		<td>{translate key=$statusString}</td>
		{if $objectForReview->getEditorId()}
			{assign var=editor value=$objectForReview->getEditor()}
			{assign var=emailString value=$editor->getFullName()|concat:" <":$editor->getEmail():">"}
			{url|assign:"url" page="user" op="email" to=$emailString|to_array redirectUrl=$currentUrl}
			<td>{$editor->getFullName()|escape}&nbsp;{icon name="mail" url=$url}</td>
		{else}
			<td>&nbsp;</td>
		{/if}
		<td>{$objectForReviewAssignment->getDateDue()|date_format:$dateFormatTrunc}</td>
		{if $status == $smarty.const.OFR_STATUS_ASSIGNED || $status == $smarty.const.OFR_STATUS_MAILED}
			<td><a href="{url page="author" op="submit"}" class="action">{translate key="plugins.generic.objectsForReview.author.submit"}</a></td>
		{elseif $status == $smarty.const.OFR_STATUS_SUBMITTED}
			{assign var=submissionId value=$objectForReviewAssignment->getSubmissionId()}
			<td><a href="{url page="author" op="submission" path=$submissionId}" class="action">{translate key="plugins.generic.objectsForReview.author.view"}</a></td>
		{else}
			<td>&nbsp;</td>
		{/if}
	</tr>
	<tr>
		<td colspan="6" class="{if $objectForReviewAssignments->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $objectForReviewAssignments->wasEmpty()}
	<tr>
		<td colspan="6" class="nodata">{translate key="plugins.generic.objectsForReview.objectForReviewAssignments.noneCreated"}</td>
	</tr>
	<tr>
		<td colspan="6" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td colspan="3" align="left">{page_info iterator=$objectForReviewAssignments}</td>
		<td colspan="3" align="right">{page_links anchor="objectForReviewAssignments" name="objectForReviewAssignments" iterator=$objectForReviewAssignments}</td>
	</tr>
{/if}
</table>
