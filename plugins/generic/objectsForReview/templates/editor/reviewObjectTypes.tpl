{**
 * @file plugins/generic/objectsForReview/templates/editor/reviewObjectTypes.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display the list of review object types.
 *
 *}
{strip}
{assign var="pageTitle" value="plugins.generic.objectsForReview.editor.objectTypes"}
{include file="common/header.tpl"}
{/strip}

<script type="text/javascript">
{literal}
<!--
function toggleChecked() {
	var elements = document.getElementById('reviewObjectTypesForm').elements;
	for (var i=0; i < elements.length; i++) {
		if (elements[i].name == 'update[]') {
			elements[i].checked = !elements[i].checked;
		}
	}
}
// -->
{/literal}
</script>

<p>{translate key="plugins.generic.objectsForReview.editor.objectTypes.description"}</p>

<div id="reviewObjectTypes">
<form id='reviewObjectTypesForm' action="{url op="updateOrInstallReviewObjectTypes"}" method="post">
<table width="100%" class="listing">
	<tr>
		<td class="headseparator" colspan="3">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="3%">{translate key="plugins.generic.objectsForReview.editor.objectTypes.update"}</td>
		<td width="72%">{translate key="plugins.generic.objectsForReview.editor.objectTypes.name"}</td>
		<td width="25%" align="right">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td class="headseparator" colspan="3">&nbsp;</td>
	</tr>
{iterate from=results item=result name=reviewObjectTypes}
	{assign var=typeKey value=$result.typeKey}
	{assign var=typeId value=$result.typeId}
	{assign var=typeName value=$result.typeName}
	{assign var=typeActive value=$result.typeActive}
	<tr valign="top" id="reviewobjecttype-{$typeId|escape}" class="data">
		<td><input type="checkbox" name="update[]" value="{$typeId|escape}"/></td>
		<td>{$typeName|escape}</td>
		<td align="right" class="nowrap">
			<a href="{url op="editReviewObjectType" path=$typeId}" class="action">{translate key="common.edit"}</a>&nbsp;|
			{strip}
				{if $typeActive}
					<a href="{url op="deactivateReviewObjectType" path=$typeId}" class="action">{translate key="common.deactivate"}</a>
				{else}
					<a href="{url op="activateReviewObjectType" path=$typeId}" class="action">{translate key="common.activate"}</a>
				{/if}
				&nbsp;|
			{/strip}
			<a href="{url op="previewReviewObjectType" path=$typeId}" class="action">{translate key="common.preview"}</a>&nbsp;|
			<a href="{url op="deleteReviewObjectType" path=$typeId}" onclick="return confirm('{translate|escape:"jsparam" key="plugins.generic.objectsForReview.editor.objectType.confirmDelete"}')" class="action">{translate key="common.delete"}</a>
		</td>
	</tr>
{/iterate}
	<tr>
		<td colspan="3" class="endseparator">&nbsp;</td>
	</tr>
{if $results->wasEmpty()}
	<tr>
		<td colspan="3" class="nodata">{translate key="plugins.generic.objectsForReview.editor.objectTypes.none"}</td>
	</tr>
	<tr>
		<td colspan="3" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td colspan="2" align="left">{page_info iterator=$results}</td>
		<td align="right">{page_links anchor="results" name="reviewObjectTypes" iterator=$results}</td>
	</tr>
{/if}
</table>

{if !$results->wasEmpty()}
<p>{translate key="plugins.generic.objectsForReview.editor.objectTypes.updateLocales"}<p>
<p><select name="updateLocales[]" class="selectMenu" size="3" multiple>{html_options options=$pluginLocales}</select>&nbsp;<input type="submit" name="updateLocaleData" value="{translate key="plugins.generic.objectsForReview.editor.objectTypes.update"}" class="button defaultButton"/>&nbsp;<input type="button" value="{translate key="common.selectAll"}" class="button" onclick="toggleChecked()" /></p>
{/if}

{if $missingReviewObjects}
<p>{translate key="plugins.generic.objectsForReview.editor.objectTypes.installObjects"}</p>
<p><select name="reviewObjects[]" class="selectMenu" size="3" multiple>{html_options options=$missingReviewObjects}</select>&nbsp;<select name="installLocales[]" class="selectMenu" size="3" multiple>{html_options options=$pluginLocales}</select>&nbsp;<input type="submit" name="installReviewObjects" value="{translate key="plugins.generic.objectsForReview.editor.objectTypes.install"}" class="button defaultButton"/></p>
{/if}
</form>

<br/>
<p><a class="action" href="{url op="createReviewObjectType"}">{translate key="plugins.generic.objectsForReview.editor.objectType.create"}</a></p>

</div>
{include file="common/footer.tpl"}

