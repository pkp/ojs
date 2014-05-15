{**
 * @file plugins/generic/objectsForReview/templates/editor/objectsForReview.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of objects for review for editor management.
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

{assign var=colspan value="6"}
{assign var=colspanPage value="3"}

<table width="100%" class="listing">
	<tr>
		<td colspan="{$colspan}" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="40%">{sort_heading key="plugins.generic.objectsForReview.objectsForReview.title" sort="title"}</td>
		<td width="20%">{sort_heading key="plugins.generic.objectsForReview.objectsForReview.objectType" sort="type"}</td>
		<td width="7%">{sort_heading key="plugins.generic.objectsForReview.objectsForReview.dateCreated" sort="created"}</td>
		<td width="5%">{sort_heading key="plugins.generic.objectsForReview.objectsForReview.editor" sort="editor"}</td>
		<td width="13%">{sort_heading key="plugins.generic.objectsForReview.objectsForReview.status" sort="status"}</td>
		<td width="15%" align="right">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td colspan="{$colspan}" class="headseparator">&nbsp;</td>
	</tr>
{iterate from=objectsForReview item=objectForReview}
{assign var=reviewObjectType value=$objectForReview->getReviewObjectType()}
	<tr valign="top">
		<td><a href="{url op="editObjectForReview" path=$objectForReview->getId() reviewObjectTypeId=$objectForReview->getReviewObjectTypeId()}" class="action">{$objectForReview->getTitle()|escape|truncate:40:"..."}</a></td>
		<td>{$reviewObjectType->getLocalizedName()|escape}</td>
		<td>{$objectForReview->getDateCreated()|date_format:$dateFormatTrunc}</td>
		<td>{$objectForReview->getEditorInitials()|escape}</td>
		{assign var=statusString value=$objectForReview->getStatusString()}
		<td>{translate key=$statusString}</td>
		<td align="right">
		{if $objectForReview->getAvailable()}
			{if $mode == $smarty.const.OFR_MODE_FULL}
				<a href="{url op="selectObjectForReviewAuthor" path=$objectForReview->getId()}" class="action">{translate key="plugins.generic.objectsForReview.editor.assignObjectReviewer"}</a> |
			{else}
				<a href="{url op="selectObjectForReviewSubmission" objectId=$objectForReview->getId() returnPage='all'}" class="action">{translate key="plugins.generic.objectsForReview.editor.select"}</a> |
			{/if}
		{/if}
			<a href="{url op="deleteObjectForReview" path=$objectForReview->getId()}" class="action" onclick="return confirm('{translate|escape:"jsparam" key="plugins.generic.objectsForReview.editor.objectForReview.confirmDelete"}')">{translate key="plugins.generic.objectsForReview.editor.objectForReview.delete"}</a>
		</td>
	</tr>
	<tr>
		<td colspan="{$colspan}" class="{if $objectsForReview->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $objectsForReview->wasEmpty() and $search != ""}
	<tr>
		<td colspan="{$colspan}" class="nodata">{translate key="plugins.generic.objectsForReview.search.noResults"}</td>
	</tr>
	<tr>
		<td colspan="{$colspan}" class="endseparator">&nbsp;</td>
	</tr>
{elseif $objectsForReview->wasEmpty()}
	<tr>
		<td colspan="{$colspan}" class="nodata">{translate key="plugins.generic.objectsForReview.objectsForReview.noneCreated"}</td>
	</tr>
	<tr>
		<td colspan="{$colspan}" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td colspan="{$colspanPage}" align="left">{page_info iterator=$objectsForReview}</td>
		<td colspan="{$colspanPage}" align="right">{page_links anchor="objectsForReview" name="objectsForReview" iterator=$objectsForReview sort=$sort sortDirection=$sortDirection filterEditor=$filterEditor filterType=$filterType searchField=$searchField searchMatch=$searchMatch search=$search}</td>
	</tr>
{/if}
</table>
