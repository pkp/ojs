{**
 * @file plugins/generic/objectsForReview/templates/editor/reviewObjectTypeForm.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form to create/modify a review object type.
 *
 *}
{strip}
{if $reviewObjectType}
	{assign var="typeId" value=$reviewObjectType->getId()}
{/if}
{include file="common/header.tpl"}
{/strip}

<script type="text/javascript">
{literal}
<!--
function togglePossibleOptions(newValue, multipleOptionsTypesString) {
	if (multipleOptionsTypesString.indexOf(';'+newValue+';') != -1) {
		document.getElementById('reviewObjectMetadataForm').addOption.disabled=false;
	} else {
		if (document.getElementById('reviewObjectMetadataForm').addOption.disabled == false) {
			alert({/literal}'{translate|escape:"jsparam" key="plugins.generic.objectsForReview.editor.objectMetadata.changeType"}'{literal});
		}
		document.getElementById('reviewObjectMetadataForm').addOption.disabled=true;
	}
}
// -->
{/literal}
</script>

{if $typeId}
	<ul class="menu">
		<li class="current"><a href="{url op="editReviewObjectType" path=$typeId}">{translate key="plugins.generic.objectsForReview.editor.objectType.edit"}</a></li>
		<li><a href="{url op="reviewObjectMetadata" path=$typeId}">{translate key="plugins.generic.objectsForReview.editor.objectType.metadata"}</a></li>
		<li><a href="{url op="previewReviewObjectType" path=$typeId}">{translate key="plugins.generic.objectsForReview.editor.objectType.preview"}</a></li>
	</ul>
{/if}

<br/>

<form id="reviewObjectTypeForm" method="post" action="{url op="updateReviewObjectType"}">
{if $typeId}
<input type="hidden" name="typeId" value="{$typeId}"/>
{/if}
{include file="common/formErrors.tpl"}

<table class="data" width="100%">
{if count($formLocales) > 1}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="formLocale" key="form.formLanguage"}</td>
		<td width="80%" class="value">
			{if $typeId}{url|assign:"reviewObjectTypeFormUrl" op="editReviewObjectType" path=$typeId escape=false}
			{else}{url|assign:"reviewObjectTypeFormUrl" op="createReviewObjectType" escape=false}
			{/if}
			{form_language_chooser form="reviewObjectTypeForm" url=$reviewObjectTypeFormUrl}
			<span class="instruct">{translate key="form.formLanguage.description"}</span>
		</td>
	</tr>
{/if}
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="name" required="true" key="plugins.generic.objectsForReview.editor.objectType.name"}</td>
	<td width="80%" class="value"><input type="text" name="name[{$formLocale|escape}]" value="{$name[$formLocale]|escape}" id="name" size="40" maxlength="120" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="description" key="plugins.generic.objectsForReview.editor.objectType.description"}</td>
	<td class="value"><textarea name="description[{$formLocale|escape}]" rows="4" cols="40" id="description" class="textArea">{$description[$formLocale]|escape}</textarea></td>
</tr>
</table>

{if !$typeId}
<table class="data" width="100%">
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="options" key="plugins.generic.objectsForReview.editor.objectType.persons"}</td>
	<td width="80%" class="value">{translate key="plugins.generic.objectsForReview.editor.objectType.persons.description"}
		<a name="possibleOptions"></a>
		{foreach name=options from=$possibleOptions[$formLocale] key=optionId item=optionItem}
			{if !$notFirstOptionItem}
				{assign var=notFirstOptionItem value=1}
				<table width="100%" class="data">
				<tr valign="top">
					<td width="5%">{translate key="common.order"}</td>
					<td width="95%" colspan="2">{translate key="plugins.generic.objectsForReview.editor.objectMetadata.possibleOptions"}</td>
				</tr>
			{/if}
				<tr valign="top">
					<td width="5%" class="label"><input type="hidden" name="possibleOptions[{$formLocale|escape}][{$optionId|escape}][order]" value="{$optionItem.order|escape}"/>{$optionItem.order|escape}</td>
					<td class="value"><textarea name="possibleOptions[{$formLocale|escape}][{$optionId|escape}][content]" id="possibleOptions-{$optionId|escape}" rows="3" cols="40" class="textArea">{$optionItem.content|escape}</textarea></td>
					<td width="100%"><input type="submit" name="delOption[{$optionId|escape}]" value="{translate key="common.delete"}" class="button" /></td>
				</tr>
		{/foreach}

		{if $notFirstOptionItem}
				</table>
		{/if}
		<br/>
		<input type="submit" name="addOption" value="{translate key="plugins.generic.objectsForReview.editor.objectMetadata.addOptionItem"}" class="button" />
	</td>
</tr>
</table>
{/if}

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="reviewObjectTypes" escape=false}'" /></p>
</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

{include file="common/footer.tpl"}

