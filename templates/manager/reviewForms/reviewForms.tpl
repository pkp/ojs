{**
 * reviewForms.tpl
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of reviewForms in journal management.
 *
 *}
{assign var="pageTitle" value="reviewForm.reviewForms"}
{include file="common/header.tpl"}

<br/>

<a name="reviewForms"></a>

<table width="100%" class="listing">
	<tr>
		<td class="headseparator" colspan="3">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="80%">{translate key="reviewForm.title"}</td>
		<td width="20%" align="right">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td class="headseparator" colspan="3">&nbsp;</td>
	</tr>
{iterate from=reviewForms item=reviewForm name=reviewForms}
	<tr valign="top">
		<td>{$reviewForm->getReviewFormTitle()|escape}</td>
		<td align="right" class="nowrap">
			<a href="{url op="editReviewForm" path=$reviewForm->getReviewFormId()}" class="action">{translate key="common.edit"}</a>&nbsp;|&nbsp;<a href="{url op="deleteReviewForm" path=$reviewForm->getReviewFormId()}" onclick="return confirm('{translate|escape:"jsparam" key="manager.reviewForms.confirmDelete"}')" class="action">{translate key="common.delete"}</a>&nbsp;|&nbsp;<a href="{url op="moveReviewForm" d=u reviewFormId=$reviewForm->getReviewFormId()}">&uarr;</a>&nbsp;<a href="{url op="moveReviewForm" d=d reviewFormId=$reviewForm->getReviewFormId()}">&darr;</a>
		</td>
	</tr>
	<tr>
		<td colspan="3" class="{if $reviewForms->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $reviewForms->wasEmpty()}
	<tr>
		<td colspan="3" class="nodata">{translate key="manager.reviewForms.noneCreated"}</td>
	</tr>
	<tr>
		<td colspan="3" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td align="left">{page_info iterator=$reviewForms}</td>
		<td colspan="2" align="right">{page_links anchor="reviewForms" name="reviewForms" iterator=$reviewForms}</td>
	</tr>
{/if}
</table>

<a class="action" href="{url op="createReviewForm"}">{translate key="manager.reviewForms.create"}</a>

{include file="common/footer.tpl"}
