{**
 * publishedReviewForms.tpl
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of published review forms in journal management.
 *
 *}
{assign var="pageTitle" value="manager.reviewForms.publishedReviewForms"}
{include file="common/header.tpl"}

<ul class="menu">
	<li><a href="{url op="unpublishedReviewForms"}">{translate key="manager.reviewForms.unpublishedReviewForms"}</a></li>
	<li class="current"><a href="{url op="publishedReviewForms"}">{translate key="manager.reviewForms.publishedReviewForms"}</a></li>
</ul>

<br/>

<a name="publishedReviewForms"></a>

<table width="100%" class="listing">
	<tr>
		<td class="headseparator" colspan="4">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="30%">{translate key="manager.reviewForms.title"}</td>
		<td width="10%">{translate key="manager.reviewForms.completed"}</td>
		<td width="10%">{translate key="manager.reviewForms.inReview"}</td>
		<td width="50%" align="right">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td class="headseparator" colspan="4">&nbsp;</td>
	</tr>
{iterate from=reviewForms item=reviewForm name=reviewForms}
	{assign var="reviewFormId" value=$reviewForm->getReviewFormId()}
	<tr valign="top">
		<td>{$reviewForm->getReviewFormTitle()|escape}</td>
		<td>{$completed[$reviewFormId]}</td>
		<td>{$active[$reviewFormId]}</td>
		<td  align="right">
			{if $reviewForm->getActive()}
				<a href="{url op="deactivateReviewForm" path=$reviewFormId}" class="action">{translate key="common.deactivate"}</a>
			{else}
				<a href="{url op="activateReviewForm" path=$reviewFormId}" class="action">{translate key="common.activate"}</a>
			{/if}
			&nbsp;|&nbsp;<a href="{url op="copyReviewForm" path=$reviewFormId}" class="action">{translate key="common.copy"}</a>&nbsp;|&nbsp;<a href="{url op="deleteReviewForm" path=$reviewFormId}" onclick="return confirm('{translate|escape:"jsparam" key="manager.reviewForms.confirmDeletePublished"}')" class="action">{translate key="common.delete"}</a>&nbsp;|&nbsp;<a href="{url op="previewReviewForm" path=$reviewFormId}" class="action">{translate key="common.preview"}</a>&nbsp;|&nbsp;<a href="{url op="moveReviewForm" d=u reviewFormId=$reviewFormId}" class="action">&uarr;</a>&nbsp;<a href="{url op="moveReviewForm" d=d reviewFormId=$reviewFormId}" class="action">&darr;</a>
		</td>
	</tr>
	<tr>
		<td colspan="4" class="{if $reviewForms->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}

{if $reviewForms->wasEmpty()}
	<tr>
		<td colspan="4" class="nodata">{translate key="manager.reviewForms.nonePublished"}</td>
	</tr>
	<tr>
		<td colspan="4" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td align="left">{page_info iterator=$reviewForms}</td>
		<td colspan="3" align="right">{page_links anchor="reviewForms" name="reviewForms" iterator=$reviewForms}</td>
	</tr>
{/if}
</table>

{include file="common/footer.tpl"}
