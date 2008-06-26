{**
 * reviewFormElementForm.tpl
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form to create/modify a review form element.
 *
 *}
{assign var="pageId" value="manager.reviewFormElements.reviewFormElementForm"}
{assign var="pageCrumbTitle" value=$pageTitle}
{include file="common/header.tpl"}

<script type="text/javascript">
{literal}
<!--
function togglePossibleResponses(newValue, multipleResponsesElementTypesString) {
	if (multipleResponsesElementTypesString.indexOf(';'+newValue+';') != -1) {
		document.reviewFormElementForm.addResponse.disabled=false;
	} else {
		if (document.reviewFormElementForm.addResponse.disabled == false) {
			alert({/literal}'{translate|escape:"jsparam" key="manager.reviewFormElement.changeType"}'{literal});
		}
		document.reviewFormElementForm.addResponse.disabled=true;
	}
}
// -->
{/literal}
</script>

<br/>
<form name="reviewFormElementForm" method="post" action="{url op="updateReviewFormElement"}">
	<input type="hidden" name="reviewFormId" value="{$reviewFormId}"/>
	<input type="hidden" name="reviewFormElementId" value="{$reviewFormElementId}"/>

{include file="common/formErrors.tpl"}

<table class="data" width="100%">
{if count($formLocales) > 1}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="formLocale" key="form.formLanguage"}</td>
		<td width="80%" class="value">
			{if $reviewFormElementId}{url|assign:"reviewFormElementFormUrl" op="editReviewFormElement" path=$reviewFormId|to_array:$reviewFormElementId}
			{else}{url|assign:"reviewFormElementFormUrl" op="createReviewFormElement"}
			{/if}
			{form_language_chooser form="reviewFormElementForm" url=$reviewFormElementFormUrl}
			<span class="instruct">{translate key="form.formLanguage.description"}</span>
		</td>
	</tr>
{/if}
<tr valign="top">
	<td class="label">{fieldLabel name="question" required="true" key="manager.reviewFormElements.question"}</td>
	<td class="value"><textarea name="question[{$formLocale|escape}]" rows="4" cols="40" id="question" class="textArea">{$question[$formLocale]|escape}</textarea></td>
</tr>
<tr valign="top">
	<td>&nbsp;</td>
	<td class="value">
		<input type="checkbox" name="required" id="required" value="1" {if $required}checked="checked"{/if} />
		{fieldLabel name="required" key="manager.reviewFormElements.required"}
	</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="elementType" required="true" key="manager.reviewFormElements.elementType"}</td>
	<td class="value">
		<select name="elementType" id="elementType" class="selectMenu" size="1" onchange="togglePossibleResponses(this.options[this.selectedIndex].value, '{$multipleResponsesElementTypesString}')">{html_options_translate options=$reviewFormElementTypeOptions selected=$elementType}</select>
	</td>
</tr>
<tr valign="top">
	<td class="label">&nbsp;</td>
	<td class="value">
		{foreach name=responses from=$possibleResponses[$formLocale] key=responseId item=responseItem}
			{if !$notFirstResponseItem}
				{assign var=notFirstResponseItem value=1}
				<table width="100%" class="data">
				<tr valign="top">
					<td width="5%">{translate key="common.order"}</td>
					<td width="95%" colspan="2">{translate key="manager.reviewFormElements.possibleResponse"}</td>
				</tr>
			{/if}
				<tr valign="top">
					<td width="5%" class="label"><input type="text" name="possibleResponses[{$formLocale|escape}][{$responseId|escape}][order]" value="{$responseItem.order|escape}" size="3" maxlength="2" class="textField" /></td>
					<td class="value"><textarea name="possibleResponses[{$formLocale|escape}][{$responseId|escape}][content]" id="possibleResponses-{$responseId}" rows="3" cols="40" class="textArea">{$responseItem.content|escape}</textarea></td>
					<td width="100%"><input type="submit" name="delResponse[{$responseId}]" value="{translate key="common.delete"}" class="button" /></td>
				</tr>
		{/foreach}

		{if $notFirstResponseItem}
				</table>
		{/if}
		<br/>
		<input type="submit" name="addResponse" value="{translate key="manager.reviewFormElements.addResponseItem"}" class="button" {if not in_array($elementType, $multipleResponsesElementTypes)}disabled="disabled"{/if}/>
	</td>
</tr>
</table>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="reviewFormElements" path=$reviewFormId escape=false}'" /></p>
</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

{include file="common/footer.tpl"}
