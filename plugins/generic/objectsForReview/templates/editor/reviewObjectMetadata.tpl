{**
 * @file plugins/generic/objectsForReview/templates/editor/reviewObjectMetadata.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of review object metadata.
 *
 *}
{strip}
{assign var="pageTitle" value="plugins.generic.objectsForReview.editor.objectMetadata"}
{include file="common/header.tpl"}
{/strip}
<script type="text/javascript">
{literal}
$(document).ready(function() { setupTableDND("#reviewObjectMetadataTable",
{/literal}
'{url|escape:"jsparam" op=moveReviewObjectMetadata}'
{literal}
); });
{/literal}
</script>

<script type="text/javascript">
{literal}
<!--
function toggleChecked() {
	var elements = document.getElementById('reviewObjectMetadataForm').elements;
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
	<li><a href="{url op="editReviewObjectType" path=$typeId}">{translate key="plugins.generic.objectsForReview.editor.objectType.edit"}</a></li>
	<li class="current"><a href="{url op="reviewObjectMetadata" path=$typeId}">{translate key="plugins.generic.objectsForReview.editor.objectType.metadata"}</a></li>
	<li><a href="{url op="previewReviewObjectType" path=$typeId}">{translate key="plugins.generic.objectsForReview.editor.objectType.preview"}</a></li>
</ul>

<br/>

<div id="reviewObjectMetadata">
<form id='reviewObjectMetadataForm' action="{url op="copyOrUpdateReviewObjectMetadata" path=$typeId}" method="post">
<table width="100%" class="listing" id="reviewObjectMetadataTable">
	<tr>
		<td class="headseparator" colspan="5">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="3%">{translate key="plugins.generic.objectsForReview.editor.objectMetadata.copy"}</td>
		<td width="67%">{translate key="plugins.generic.objectsForReview.editor.objectMetadata.name"}</td>
		<td width="5%">{translate key="plugins.generic.objectsForReview.editor.objectMetadata.required"}</td>
		<td width="5%">{translate key="plugins.generic.objectsForReview.editor.objectMetadata.display"}</td>
		<td width="20%" align="right">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td class="headseparator" colspan="5">&nbsp;</td>
	</tr>
{iterate from=reviewObjectMetadata item=metadata name=reviewObjectMetadata}
	{if $metadata->getKey() == ''}
		{assign var=reviewObjectMetadataToCopyExists value=1}
	{/if}
	<tr valign="top" id="formelt-{$metadata->getId()|escape}" class="data">
		<td>{if $metadata->getKey() == null}<input type="checkbox" name="copy[]" value="{$metadata->getId()|escape}"/>{else}&nbsp;{/if}</td>
		<td class="drag">{$metadata->getLocalizedName()|escape}</td>
		<td><input type="checkbox" name="required[]" value="{$metadata->getId()|escape}"{if $metadata->getRequired()} checked="checked"{/if}/></td>
		<td><input type="checkbox" name="display[]" value="{$metadata->getId()|escape}"{if $metadata->getDisplay()} checked="checked"{/if}/></td>
		<td  align="right" class="nowrap">
			{if !$metadata->keyExists()}<a href="{url op="editReviewObjectMetadata" path=$metadata->getReviewObjectTypeId()|to_array:$metadata->getId()}" class="action">{translate key="common.edit"}</a>&nbsp;|&nbsp;<a href="{url op="deleteReviewObjectMetadata" path=$metadata->getReviewObjectTypeId()|to_array:$metadata->getId()}" onclick="return confirm('{translate|escape:"jsparam" key="plugins.generic.objectsForReview.editor.objectMetadata.confirmDelete"}')" class="action">{translate key="common.delete"}</a>&nbsp;|&nbsp;{/if}<a href="{url op="moveReviewObjectMetadata" d=u id=$metadata->getId()}" class="action">&uarr;</a>&nbsp;<a href="{url op="moveReviewObjectMetadata" d=d id=$metadata->getId()}" class="action">&darr;</a>
		</td>
	</tr>
{/iterate}

{if $reviewObjectMetadata->wasEmpty()}
	<tr>
		<td colspan="5" class="nodata">{translate key="plugins.generic.objectsForReview.editor.objectMetadata.none"}</td>
	</tr>
{/if}
	<tr>
		<td colspan="5" class="endseparator">&nbsp;</td>
	</tr>
	<tr>
		<td colspan="2" align="left">
			{if $reviewObjectMetadataToCopyExists}
				<p>{translate key="plugins.generic.objectsForReview.editor.objectMetadata.copyTo"}&nbsp;<select name="targetReviewObjectTypeId" class="selectMenu" size="1">{html_options options=$typeOptions}</select>&nbsp;<input type="submit" value="{translate key="common.copy"}" class="button defaultButton"/>&nbsp;<input type="button" value="{translate key="common.selectAll"}" class="button" onclick="toggleChecked()" /></p>
			{/if}
		</td>
		<td colspan="3" align="left">
			<input type="submit" name="save" value="{translate key="common.save"}" class="button defaultButton" />
		</td>
	</tr>
</table>
</form>

<br />

<a class="action" href="{url op="createReviewObjectMetadata" path=$typeId}">{translate key="plugins.generic.objectsForReview.editor.objectMetadata.create"}</a>
</div>
{include file="common/footer.tpl"}

