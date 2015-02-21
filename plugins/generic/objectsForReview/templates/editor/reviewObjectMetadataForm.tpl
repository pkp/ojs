{**
 * @file plugins/generic/objectsForReview/templates/editor/reviewObjectMetadataForm.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form to create/modify a review object metadata.
 *
 *}
{strip}
{if $reviewObjectMetadata}
	{assign var="metadataId" value=$reviewObjectMetadata->getId()}
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

<br/>
<form id="reviewObjectMetadataForm" method="post" action="{url op="updateReviewObjectMetadata" anchor="possibleOptions"}">
<input type="hidden" name="reviewObjectTypeId" value="{$reviewObjectTypeId|escape}"/>
<input type="hidden" name="metadataId" value="{$metadataId|escape}"/>
{include file="common/formErrors.tpl"}

<table class="data" width="100%">
{if count($formLocales) > 1}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="formLocale" key="form.formLanguage"}</td>
		<td width="80%" class="value">
			{if $metadataId}{url|assign:"reviewObjectMetadataFormUrl" op="editReviewObjectMetadata" path=$reviewObjectTypeId|to_array:$metadataId escape=false}
			{else}{url|assign:"reviewObjectMetadataFormUrl" op="createReviewObjectMetadata" path=$reviewObjectTypeId escape=false}
			{/if}
			{form_language_chooser form="reviewObjectMetadataForm" url=$reviewObjectMetadataFormUrl}
			<span class="instruct">{translate key="form.formLanguage.description"}</span>
		</td>
	</tr>
{/if}
<tr valign="top">
	<td class="label">{fieldLabel name="name" required="true" key="plugins.generic.objectsForReview.editor.objectMetadata.name"}</td>
	<td class="value"><input type="text" name="name[{$formLocale|escape}]" value="{$name[$formLocale]|escape}" id="name" size="40" maxlength="120" class="textField" /></td>
</tr>
<tr valign="top">
	<td>&nbsp;</td>
	<td class="value">
		<input type="checkbox" name="required" id="required" value="1"{if $required} checked="checked"{/if} />
		{fieldLabel name="required" key="plugins.generic.objectsForReview.editor.objectMetadata.required"}
	</td>
</tr>
<tr valign="top">
	<td>&nbsp;</td>
	<td class="value">
		<input type="checkbox" name="display" id="display" value="1"{if $display} checked="checked"{/if} />
		{fieldLabel name="required" key="plugins.generic.objectsForReview.editor.objectMetadata.display"}
	</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="metadataType" required="true" key="plugins.generic.objectsForReview.editor.objectMetadata.metadataType"}</td>
	<td class="value">
		<select name="metadataType" id="metadataType" class="selectMenu" size="1" onchange="togglePossibleOptions(this.options[this.selectedIndex].value, '{$multipleOptionsTypesString}')">{html_options_translate options=$metadataTypeOptions selected=$metadataType}</select>
	</td>
</tr>
<tr valign="top">
	<td class="label">&nbsp;</td>
	<td class="value">
		<a name="possibleOptions"></a>
		{foreach name=options from=$possibleOptions[$formLocale] key=optionId item=optionItem}
			{if !$notFirstOptionItem}
				{assign var=notFirstOptionItem value=1}
				<table width="100%" class="data">
				<tr valign="top">
					<td width="8%">{translate key="common.order"}</td>
					<td width="92%" colspan="2">{translate key="plugins.generic.objectsForReview.editor.objectMetadata.possibleOptions"}</td>
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
		<input type="submit" name="addOption" value="{translate key="plugins.generic.objectsForReview.editor.objectMetadata.addOptionItem"}" class="button" {if not in_array($metadataType, $multipleOptionsTypes)}disabled="disabled"{/if}/>
	</td>
</tr>
</table>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="reviewObjectMetadata" path=$reviewObjectTypeId escape=false}'" /></p>
</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

{include file="common/footer.tpl"}

