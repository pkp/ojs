{**
 * templates/manager/reviewForms/reviewFormElements.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
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
$(document).ready(function() { setupTableDND("#reviewFormElementsTable",
{/literal}
"{url op=moveReviewFormElement}"
{literal}
); });
{/literal}
</script>

<script type="text/javascript">
{literal}
<!--
function toggleChecked() {
	var elements = document.getElementById('reviewFormElements').elements;
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

<div id="reviewFormElements">
<form id="reviewFormElements" action="{url op="copyReviewFormElement"}" method="post">
<table width="100%" class="listing" id="reviewFormElementsTable">
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
	<tr valign="top" id="formelt-{$reviewFormElement->getId()}" class="data">
		<td><input type="checkbox" name="copy[]" value="{$reviewFormElement->getId()|escape}"/></td>
		<td class="drag">{$reviewFormElement->getLocalizedQuestion()|truncate:200:"..."}</td>
		<td class="nowrap">
			<a href="{url op="editReviewFormElement" path=$reviewFormElement->getReviewFormId()|to_array:$reviewFormElement->getId()}" class="action">{translate key="common.edit"}</a>&nbsp;|&nbsp;<a href="{url op="deleteReviewFormElement" path=$reviewFormElement->getReviewFormId()|to_array:$reviewFormElement->getId()}" onclick="return confirm('{translate|escape:"jsparam" key="manager.reviewFormElements.confirmDelete"}')" class="action">{translate key="common.delete"}</a>&nbsp;|&nbsp;<a href="{url op="moveReviewFormElement" d=u id=$reviewFormElement->getId()}" class="action">&uarr;</a>&nbsp;<a href="{url op="moveReviewFormElement" d=d id=$reviewFormElement->getId()}" class="action">&darr;</a>
		</td>
	</tr>
  {if $reviewFormElements->eof()}
    <tr><td class="endseparator" colspan="3"></td></tr>
  {/if}
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
</div>
{include file="common/footer.tpl"}

