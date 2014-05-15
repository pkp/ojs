{**
 * @file plugins/generic/objectsForReview/templates/objectForReviewForm.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Object for review form.
 *
 *}

{include file="common/header.tpl"}

<br/>
<form id="objectForReviewForm" method="post" action="{url op="updateObjectForReview"}" enctype="multipart/form-data">
<input type="hidden" name="objectId" value="{$objectId|escape}" />
<input type="hidden" name="reviewObjectTypeId" value="{$reviewObjectType->getId()|escape}" />
{include file="common/formErrors.tpl"}

{literal}
<script type="text/javascript">
<!--
// Move person up/down
function movePerson(dir, personIndex) {
	var form = document.getElementById('objectForReviewForm');
	form.movePerson.value = 1;
	form.movePersonDir.value = dir;
	form.movePersonIndex.value = personIndex;
	form.submit();
}
// -->
</script>
{/literal}

<div id="objectForReviewFormDetails">

<input type="hidden" name="deletedPersons" value="{$deletedPersons|escape}" />
<input type="hidden" name="movePerson" value="0" />
<input type="hidden" name="movePersonDir" value="" />
<input type="hidden" name="movePersonIndex" value="" />

<p>{$reviewObjectType->getLocalizedDescription()|escape}</p>
<br />

<table class="data" width="100%">
{foreach from=$reviewObjectMetadata name=reviewObjectMetadata key=metadataId item=metadata}
	{assign var=ofrSettingValue value=$ofrSettings.$metadataId}
	{if $metadata->getMetadataType() == REVIEW_OBJECT_METADATA_TYPE_ROLE_DROP_DOWN_BOX}
		{foreach name=persons from=$persons key=personIndex item=person}
		<tr valign="top">
			<td width="20%" class="label">{$metadata->getLocalizedName()|escape} {if $metadata->getRequired() == 1}*{/if}</td>
			<td width="80%" class="value">
				<select name="persons[{$personIndex|escape}][role]" id="persons-{$personIndex|escape}-role" size="1" class="selectMenu">
					<option label="{translate key="plugins.generic.objectsForReview.editor.objectForReview.chooseRole"}" value="">{translate key="plugins.generic.objectsForReview.editor.objectForReview.chooseRole"}</option>
					{assign var=possibleOptions value=$metadata->getLocalizedPossibleOptions()}
					{foreach name=roleOptions from=$possibleOptions key=optionId item=optionItem}
					<option label="{$optionItem.content|escape}" value="{$optionItem.order|escape}"{if $optionItem.order == $person.role} selected="selected"{/if}>{$optionItem.content|escape}</option>
					{/foreach}
				</select>
			</td>
		</tr>
		<tr valign="top">
			<td width="20%" class="label">{fieldLabel name="persons-$personIndex-firstName" key="user.firstName"}
				<input type="hidden" name="persons[{$personIndex|escape}][personId]" value="{$person.personId|escape}" />
				<input type="hidden" name="persons[{$personIndex|escape}][seq]" value="{$personIndex+1|escape}" />
			</td>
			<td width="80%" class="value">
				<input type="text" name="persons[{$personIndex|escape}][firstName]" id="persons-{$personIndex|escape}-firstName" value="{$person.firstName|escape}" size="20" maxlength="40" class="textField" />
			</td>
		</tr>
		<tr valign="top">
			<td class="label">{fieldLabel name="persons-$personIndex-middleName" key="user.middleName"}</td>
			<td class="value"><input type="text" name="persons[{$personIndex|escape}][middleName]" id="persons-{$personIndex|escape}-middleName" value="{$person.middleName|escape}" size="20" maxlength="40" class="textField" /></td>
		</tr>
		<tr valign="top">
			<td class="label">{fieldLabel name="persons-$personIndex-lastName" key="user.lastName"}</td>
			<td class="value"><input type="text" name="persons[{$personIndex|escape}][lastName]" id="persons-{$personIndex|escape}-lastName" value="{$person.lastName|escape}" size="20" maxlength="90" class="textField" /></td>
		</tr>
		{if $smarty.foreach.persons.total > 1}
			<tr valign="top">
				<td class="label">&nbsp;</td>
				<td class="value"><a href="javascript:movePerson('u', '{$personIndex|escape}')" class="action plain">&uarr;</a> <a href="javascript:movePerson('d', '{$personIndex|escape}')" class="action plain">&darr;</a>&nbsp;&nbsp;&nbsp;<input type="submit" name="delPerson[{$personIndex|escape}]" value="{translate key="plugins.generic.objectsForReview.editor.objectForReview.deletePerson"}" class="button" /></td>
			</tr>
			<tr>
				<td colspan="2">&nbsp;</td>
			</tr>
		{/if}
		{foreachelse}
			{assign var=possibleOptions value=$metadata->getLocalizedPossibleOptions()}
			{if !empty($possibleOptions)}
			<tr valign="top">
				<td width="20%" class="label">{$metadata->getLocalizedName()|escape} {if $metadata->getRequired() == 1}*{/if}</td>
				<td width="80%" class="value">
					<select name="persons[0][role]" id="persons-0-role" size="1" class="selectMenu">
						<option label="{translate key="plugins.generic.objectsForReview.editor.objectForReview.chooseRole"}" value="">{translate key="plugins.generic.objectsForReview.editor.objectForReview.chooseRole"}</option>
						{foreach name=roleOptions from=$possibleOptions key=optionId item=optionItem}
						<option label="{$optionItem.content|escape}" value="{$optionItem.order|escape}">{$optionItem.content|escape}</option>
						{/foreach}
					</select>
				</td>
			</tr>
			<tr valign="top">
				<td width="20%" class="label">{fieldLabel name="persons-0-firstName" key="user.firstName"}
					<input type="hidden" name="persons[0][personId]" value="0" />
					<input type="hidden" name="persons[0][seq]" value="1" />
				</td>
				<td width="80%" class="value">
					<input type="text" name="persons[0][firstName]" id="persons-0-firstName" size="20" maxlength="40" class="textField" />
				</td>
			</tr>
			<tr valign="top">
				<td class="label">{fieldLabel name="persons-0-middleName" key="user.middleName"}</td>
				<td class="value"><input type="text" name="persons[0][middleName]" id="persons-0-middleName" size="20" maxlength="40" class="textField" /></td>
			</tr>
			<tr valign="top">
				<td class="label">{fieldLabel name="persons-0-lastName" key="user.lastName"}</td>
				<td class="value"><input type="text" name="persons[0][lastName]" id="persons-0-lastName" size="20" maxlength="90" class="textField" /></td>
			</tr>
			{/if}
		{/foreach}
		{if !empty($possibleOptions)}
		<tr valign="top">
			<td width="20%" class="label">&nbsp;</td>
			<td width="80%" class="value"><input type="submit" class="button" name="addPerson" value="{translate key="plugins.generic.objectsForReview.editor.objectForReview.addPerson"}" /></td>
		</tr>
		<tr valign="top">
			<td colspan="2">&nbsp;</td>
		</tr>
		{/if}
	{elseif $metadata->getMetadataType() == REVIEW_OBJECT_METADATA_TYPE_COVERPAGE}
		<tr valign="top">
			<td colspan="2">&nbsp;</td>
		</tr>
		<tr valign="top">
			<td width="20%" class="label">{fieldLabel name="coverPage" key="plugins.generic.objectsForReview.editor.objectForReview.coverImage"}</td>
			<td width="80%" class="value"><input type="file" name="coverPage" id="coverPage" class="uploadField" />&nbsp;&nbsp;{translate key="form.saveToUpload"}<br />{translate key="plugins.generic.objectsForReview.editor.objectForReview.coverPageInstructions"}<br />{translate key="plugins.generic.objectsForReview.editor.objectForReview.coverPageUploaded"}:&nbsp;{if $fileName}<a href="javascript:openWindow('{$publicFilesDir}/{$fileName|escape:"url"}');" class="file">{$originalFileName|escape}</a>&nbsp;<a href="{url op="removeObjectForReviewCoverPage" path=$objectId}" class="action" onclick="return confirm('{translate|escape:"jsparam" key="plugins.generic.objectsForReview.editor.objectForReview.removeCoverPage"}')">{translate key="plugins.generic.objectsForReview.editor.objectForReview.remove"}</a>{else}&mdash;{/if}</td>
		</tr>
		<tr valign="top">
			<td width="20%" class="label">{fieldLabel name="coverPageAltText" key="plugins.generic.objectsForReview.editor.objectForReview.coverImageAltText"}</td>
			<td width="80%" class="value"><input type="text" name="coverPageAltText" value="{$coverPageAltText|escape}" size="60" maxlength="255" class="textField" /></td>
		</tr>
		<tr valign="top">
			<td>&nbsp;</td>
			<td class="value"><span class="instruct">{translate key="common.altTextInstructions"}</span></td>
		</tr>
		<tr valign="top">
			<td colspan="2">&nbsp;</td>
		</tr>
	{else}
		<tr valign="top">
			<td width="20%" class="label">{$metadata->getLocalizedName()|escape} {if $metadata->getRequired() == 1}*{/if}</td>
			<td width="80%" class="value">
				{if $metadata->getMetadataType() == REVIEW_OBJECT_METADATA_TYPE_SMALL_TEXT_FIELD}
					<input type="text" name="ofrSettings[{$metadataId|escape}]" id="ofrSettings-{$metadataId|escape}" value="{$ofrSettingValue|escape}" size="20" maxlength="40" class="textField" />
				{elseif $metadata->getMetadataType() == REVIEW_OBJECT_METADATA_TYPE_TEXT_FIELD}
					<input type="text" name="ofrSettings[{$metadataId|escape}]" id="ofrSettings-{$metadataId|escape}" value="{$ofrSettingValue|escape}" size="60" maxlength="255" class="textField" />
				{elseif $metadata->getMetadataType() == REVIEW_OBJECT_METADATA_TYPE_TEXTAREA}
					<textarea name="ofrSettings[{$metadataId|escape}]" id="ofrSettings-{$metadataId|escape}" rows="4" cols="60" class="textArea">{$ofrSettingValue|escape}</textarea>
				{elseif $metadata->getMetadataType() == REVIEW_OBJECT_METADATA_TYPE_CHECKBOXES}
					{assign var=possibleOptions value=$metadata->getLocalizedPossibleOptions()}
					{foreach name=options from=$possibleOptions key=optionId item=optionItem}
						<input type="checkbox" name="ofrSettings[{$metadataId|escape}][]" id="ofrSettings-{$metadataId|escape}-{$optionItem.order|escape}" value="{$optionItem.order|escape}"{if !empty($ofrSettingValue) && in_array($optionItem.order, $ofrSettingValue)} checked="checked"{/if} />
						<label for="ofrSettings-{$metadataId|escape}-{$optionItem.order|escape}">{$optionItem.content|escape}</label>
						<br/>
					{/foreach}
				{elseif $metadata->getMetadataType() == REVIEW_OBJECT_METADATA_TYPE_RADIO_BUTTONS}
					{assign var=possibleOptions value=$metadata->getLocalizedPossibleOptions()}
					{foreach name=options from=$possibleOptions key=optionId item=optionItem}
						<input type="radio" name="ofrSettings[{$metadataId|escape}]" id="ofrSettings-{$metadataId|escape}-{$optionItem.order|escape}" value="{$optionItem.order|escape}"{if $optionItem.order == $ofrSettingValue} checked="checked"{/if}/><label for="ofrSettings-{$metadataId|escape}-{$optionItem.order|escape}">{$optionItem.content|escape}</label><br/>
					{/foreach}
					<input type="radio" name="ofrSettings[{$metadataId|escape}]" id="ofrSettings-{$metadataId|escape}-0" value=""{if 0 == $ofrSettingValue} checked="checked"{/if}/><label for="ofrSettings-{$metadataId|escape}-0">{translate key="plugins.generic.objectsForReview.editor.objectForReview.noAnswer"}</label><br/>
				{elseif $metadata->getMetadataType() == REVIEW_OBJECT_METADATA_TYPE_LANG_DROP_DOWN_BOX}
					<select name="ofrSettings[{$metadataId|escape}][]" id="ofrSettings-{$metadataId|escape}" size="5" class="selectMenu" multiple="multiple">
						{html_options options=$validLanguages selected=$ofrSettingValue}
					</select>
				{elseif $metadata->getMetadataType() == REVIEW_OBJECT_METADATA_TYPE_DROP_DOWN_BOX}
					<select name="ofrSettings[{$metadataId|escape}]" id="ofrSettings-{$metadataId|escape}" size="1" class="selectMenu">
						<option label="{translate key="plugins.generic.objectsForReview.editor.objectForReview.chooseOption"}" value="">{translate key="plugins.generic.objectsForReview.editor.objectForReview.chooseOption"}</option>
						{assign var=possibleOptions value=$metadata->getLocalizedPossibleOptions()}
						{foreach name=options from=$possibleOptions key=optionId item=optionItem}
							<option label="{$optionItem.content|escape}" value="{$optionItem.order}"{if $optionItem.order == $ofrSettingValue} selected="selected"{/if}>{$optionItem.content|escape}</option>
						{/foreach}
					</select>
				{/if}
			</td>
		</tr>
	{/if}
{/foreach}
</table>
</div>

<div class="separator"></div>
<div id="objectForReviewNotes">
<h3>{translate key="plugins.generic.objectsForReview.editor.objectForReview.additionalNotes"}</h3>
<p>{translate key="plugins.generic.objectsForReview.editor.objectForReview.notesInstructions"}</p>
<table class="data" width="100%">
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="notes" key="plugins.generic.objectsForReview.editor.objectForReview.notes"}</td>
	<td width="80%" class="value"><textarea name="notes" id="notes" cols="60" rows="6" class="textArea">{$notes|escape}</textarea></td>
</tr>
</table>
</div>

<div class="separator"></div>
<div id="objectForReviewEditors">
<h3>{translate key="plugins.generic.objectsForReview.editor.objectForReview.assignEditor"}</h3>
<p>{translate key="plugins.generic.objectsForReview.editor.objectForReview.assignEditorInstructions"}</p>
<table class="data" width="100%">
<tr valign="top">
	<td width="20%" class="label">{translate key="plugins.generic.objectsForReview.editor.objectForReview.editor"}</td>
	<td width="80%" class="value">
		<select name="editorId" id="editorId" size="1" class="selectMenu">
			{html_options options=$editors selected=$editorId}
		</select>
	</td>
</tr>
</table>
</div>

<div class="separator"></div>
<div id="objectForReviewAvailable">
<h3>{translate key="plugins.generic.objectsForReview.editor.objectForReview.available"}</h3>
<p>{translate key="plugins.generic.objectsForReview.editor.objectForReview.availableInstructions"}</p>
<table class="data" width="100%">
<tr valign="top">
	<td width="20%" class="label">{translate key="plugins.generic.objectsForReview.editor.objectForReview.available"}</td>
	<td width="80%" class="value">
		<input type="checkbox" name="available" id="available" value="1"{if ($available)} checked="checked"{/if} />
	</td>
</tr>
</table>
</div>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> {if not $objectId}<input type="submit" name="createAnother" value="{translate key="plugins.generic.objectsForReview.editor.saveAndCreateAnother"}" class="button" /> {/if}<input type="button" value="{translate key="common.cancel"}" class="button" onclick="history.go(-1);" /></p>

</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

{include file="common/footer.tpl"}
