{**
 * @file plugins/generic/objectsForReview/templates/editor/submissions.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Selection form for object for review submissions.
 *
 *}
{assign var="pageTitle" value="plugins.generic.objectsForReview.editor.selectSubmission"}
{include file="common/header.tpl"}

<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#submit').pkpHandler('$.pkp.controllers.form.FormHandler');
	{rdelim});
</script>
<form class="pkp_form" method="post" id="submit" action="{url op="selectObjectForReviewSubmission" path=$assignmentId returnPage=$returnPage}">
	<select name="searchField" size="1" class="selectMenu">
		{html_options_translate options=$searchFieldOptions selected=$searchField}
	</select>
	<select name="searchMatch" size="1" class="selectMenu">
		<option value="contains"{if $searchMatch == 'contains'} selected="selected"{/if}>{translate key="form.contains"}</option>
		<option value="is"{if $searchMatch == 'is'} selected="selected"{/if}>{translate key="form.is"}</option>
	</select>
	<input type="text" size="15" name="search" class="textField" value="{$search|escape}" />&nbsp;<input type="submit" value="{translate key="common.search"}" class="button" />
</form>

<br />

<a name="submissions"></a>

<table width="100%" class="listing">
	<tr>
		<td colspan="5" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="5%">{translate key="common.id"}</td>
		<td width="5%">{translate key="submissions.sec"}</td>
		<td width="30%">{translate key="article.authors"}</td>
		<td width="50%">{translate key="article.title"}</td>
		<td width="10%" align="right"></td>
	</tr>
	<tr>
		<td colspan="5" class="headseparator">&nbsp;</td>
	</tr>
{iterate from=submissions item=submission}
	<tr valign="top">
		<td>{$submission->getId()}</td>
		<td>{$submission->getSectionAbbrev()|escape}</td>
		<td>{$submission->getAuthorString(true)|truncate:40:"..."|escape}</td>
		<td><a href="{url page="editor" op="submission" path=$submission->getId()}" class="action">{$submission->getLocalizedTitle()|strip_tags|truncate:60:"..."}</a></td>
		<td align="right" class="nowrap">
			<a href="{url op="assignObjectForReviewSubmission" path=$assignmentId objectId=$objectId submissionId=$submission->getId() returnPage=$returnPage}" class="action">{translate key="plugins.generic.objectsForReview.editor.select"}</a>
	</td>
	</tr>
	<tr>
		<td colspan="5" class="{if $submissions->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $submissions->wasEmpty()}
	<tr>
		<td colspan="5" class="nodata">{translate key="editor.submissions.noSubmissions"}</td>
	</tr>
	<tr>
		<td colspan="5" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td colspan="4" align="left">{page_info iterator=$submissions}</td>
		<td align="right">{page_links anchor="submissions" name="submissions" iterator=$submissions searchField=$searchField searchMatch=$searchMatch search=$search}</td>
	</tr>
{/if}
</table>

{include file="common/footer.tpl"}
