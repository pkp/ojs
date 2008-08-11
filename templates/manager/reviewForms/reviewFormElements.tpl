{**
 * reviewFormElements.tpl
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of review form elements.
 *
 *}
{strip}
{assign var="pageTitle" value="manager.reviewFormElements"}
{include file="common/header.tpl"}
{/strip}

<script type="text/javascript">
{literal}
<!--
function toggleChecked() {
	var elements = document.reviewFormElements.elements;
	for (var i=0; i < elements.length; i++) {
		if (elements[i].name == 'copy[]') {
			elements[i].checked = !elements[i].checked;
		}
	}
}
// -->
{/literal}
</script>

<ul class="menu">
	<li><a href="{url op="editReviewForm" path=$reviewFormId}">{translate key="manager.reviewForms.edit"}</a></li>
	<li class="current"><a href="{url op="reviewFormElements" path=$reviewFormId}">{translate key="manager.reviewFormElements"}</a></li>
	<li><a href="{url op="previewReviewForm" path=$reviewFormId}">{translate key="manager.reviewForms.preview"}</a></li>
</ul>

<br/>

<a name="reviewFormElements"></a>

<form name="reviewFormElements" action="{url op="copyReviewFormElement"}" method="post">
<table width="100%" class="listing">
	<tr>
		<td class="headseparator" colspan="3">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="3%">&nbsp;</td>
		<td width="77%">{translate key="manager.reviewFormElements.question"}</td>
		<td width="20%">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td class="headseparator" colspan="3">&nbsp;</td>
	</tr>
{iterate from=reviewFormElements item=reviewFormElement name=reviewFormElements}
{assign var=reviewFormElementExists value=1}
	<tr valign="top">
		<td><input type="checkbox" name="copy[]" value="{$reviewFormElement->getReviewFormElementId()|escape}"/></td>
		<td>{$reviewFormElement->getReviewFormElementQuestion()|strip_unsafe_html|truncate:200:"..."|escape}</td>
		<td class="nowrap">
			<a href="{url op="editReviewFormElement" path=$reviewFormElement->getReviewFormId()|to_array:$reviewFormElement->getReviewFormElementId()}" class="action">{translate key="common.edit"}</a>&nbsp;|&nbsp;<a href="{url op="deleteReviewFormElement" path=$reviewFormElement->getReviewFormId()|to_array:$reviewFormElement->getReviewFormElementId()}" onclick="return confirm('{translate|escape:"jsparam" key="manager.reviewFormElements.confirmDelete"}')" class="action">{translate key="common.delete"}</a>&nbsp;|&nbsp;<a href="{url op="moveReviewFormElement" d=u reviewFormElementId=$reviewFormElement->getReviewFormElementId()}" class="action">&uarr;</a>&nbsp;<a href="{url op="moveReviewFormElement" d=d reviewFormElementId=$reviewFormElement->getReviewFormElementId()}" class="action">&darr;</a>
		</td>
	</tr>
	<tr>
		<td colspan="3" class="{if $reviewFormElements->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}

{if $reviewFormElements->wasEmpty()}
	<tr>
		<td colspan="3" class="nodata">{translate key="manager.reviewFormElements.noneCreated"}</td>
	</tr>
	<tr>
		<td colspan="3" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td colspan="2" align="left">{page_info iterator=$reviewFormElements}</td>
		<td align="right">{page_links anchor="reviewFormElements" name="reviewFormElements" iterator=$reviewFormElements}</td>
	</tr>
{/if}

</table>

{if $reviewFormElementExists}
	<p>{translate key="manager.reviewFormElements.copyTo"}&nbsp;<select name="targetReviewForm" class="selectMenu" size="1">{html_options options=$unusedReviewFormTitles}</select>&nbsp;<input type="submit" value="{translate key="common.copy"}" class="button defaultButton"/>&nbsp;<input type="button" value="{translate key="common.selectAll"}" class="button" onclick="toggleChecked()" /></p>
{/if}
</form>

<br />

<a class="action" href="{url op="createReviewFormElement" path=$reviewFormId}">{translate key="manager.reviewFormElements.create"}</a>

{include file="common/footer.tpl"}
