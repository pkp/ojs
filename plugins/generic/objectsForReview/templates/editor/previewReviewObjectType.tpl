{**
 * @file plugins/generic/objectsForReview/templates/editor/previewReviewObjectType.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Preview of a review object type.
 *
 *}
{strip}
{include file="common/header.tpl"}
{/strip}

{assign var=typeId value=$reviewObjectType->getId()}

<ul class="menu">
	<li><a href="{url op="editReviewObjectType" path=$typeId}">{translate key="plugins.generic.objectsForReview.editor.objectType.edit"}</a></li>
	<li><a href="{url op="reviewObjectMetadata" path=$typeId}">{translate key="plugins.generic.objectsForReview.editor.objectType.metadata"}</a></li>
	<li class="current"><a href="{url op="previewReviewObjectType" path=$typeId}">{translate key="plugins.generic.objectsForReview.editor.objectType.preview"}</a></li>
</ul>

<br/>

<h3>{$reviewObjectType->getLocalizedName()|escape}</h3>
<p>{$reviewObjectType->getLocalizedDescription()|escape}</p>

<div id="previewReviewObjectType">
<table class="data" width="100%">
{foreach from=$reviewObjectMetadata name=reviewObjectMetadata item=metadata}
	{if $metadata->getMetadataType() == REVIEW_OBJECT_METADATA_TYPE_ROLE_DROP_DOWN_BOX}
		{assign var=possibleOptions value=$metadata->getLocalizedPossibleOptions()}
		{if !empty($possibleOptions)}
		<tr valign="top">
			<td width="20%" class="label">{$metadata->getLocalizedName()|escape} {if $metadata->getRequired() == 1}*{/if}</td>
			<td width="80%" class="value">
				<select size="1" class="selectMenu">
					<option label="{translate key="plugins.generic.objectsForReview.editor.objectForReview.chooseRole"}" value="">{translate key="plugins.generic.objectsForReview.editor.objectForReview.chooseRole"}</option>
					{foreach name=options from=$possibleOptions key=optionId item=optionItem}
						<option>{$optionItem.content|escape}</option>
					{/foreach}
				</select><br/>
			</td>
		</tr>
		<tr valign="top">
			<td width="20%" class="label">{fieldLabel key="user.firstName"}</td>
			<td width="80%" class="value"><input type="text" size="20" maxlength="40" class="textField" /></td>
		</tr>
		<tr valign="top">
			<td class="label">{fieldLabel key="user.middleName"}</td>
			<td class="value"><input type="text" size="20" maxlength="40" class="textField" /></td>
		</tr>
		<tr valign="top">
			<td class="label">{fieldLabel key="user.lastName"}</td>
			<td class="value"><input type="text" size="20" maxlength="90" class="textField" /></td>
		</tr>
		<tr valign="top">
			<td width="20%" class="label">&nbsp;</td>
			<td width="80%" class="value"><input type="button" class="button" value="{translate key="plugins.generic.objectsForReview.editor.objectForReview.addPerson"}" /></td>
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
		<td width="20%" class="label">{fieldLabel key="coverPage" key="plugins.generic.objectsForReview.editor.objectForReview.coverImage"}</td>
		<td width="80%" class="value"><input type="file" class="uploadField" />&nbsp;&nbsp;{translate key="form.saveToUpload"}<br />{translate key="plugins.generic.objectsForReview.editor.objectForReview.coverPageInstructions"}<br />{translate key="plugins.generic.objectsForReview.editor.objectForReview.coverPageUploaded"}: &mdash;</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="coverPageAltText" key="plugins.generic.objectsForReview.editor.objectForReview.coverImageAltText"}</td>
		<td width="80%" class="value"><input type="text" size="60" maxlength="255" class="textField" /></td>
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
		<td width="20%" class="label">{$metadata->getLocalizedName()|escape} {if $metadata->getRequired()}*{/if}</td>
		<td width="80%" class="value">
			{if $metadata->getMetadataType() == REVIEW_OBJECT_METADATA_TYPE_SMALL_TEXT_FIELD}
				<input type="text" size="20" maxlength="40" class="textField" />
			{elseif $metadata->getMetadataType() == REVIEW_OBJECT_METADATA_TYPE_TEXT_FIELD}
				<input type="text" size="60" maxlength="255" class="textField" />
			{elseif $metadata->getMetadataType() == REVIEW_OBJECT_METADATA_TYPE_TEXTAREA}
				<textarea id="textarea_metadata_type" rows="4" cols="60" class="textArea"></textarea>
			{elseif $metadata->getMetadataType() == REVIEW_OBJECT_METADATA_TYPE_CHECKBOXES}
				{assign var=possibleOptions value=$metadata->getLocalizedPossibleOptions()}
				{foreach name=options from=$possibleOptions key=optionId item=optionItem}
					<input id="check-{$optionItem.order|escape}" type="checkbox"/>
					<label for="check-{$optionItem.order|escape}">{$optionItem.content|escape}</label>
					<br/>
				{/foreach}
			{elseif $metadata->getMetadataType() == REVIEW_OBJECT_METADATA_TYPE_RADIO_BUTTONS}
				{assign var=possibleOptions value=$metadata->getLocalizedPossibleOptions()}
				{foreach name=options from=$possibleOptions key=optionId item=optionItem}
					<input name="radio" id="radio-{$optionItem.order|escape}" type="radio"/>
					<label for="radio-{$optionItem.order|escape}">{$optionItem.content|escape}</label>
					<br/>
				{/foreach}
				<input name="radio" id="radio-0" type="radio"/>{fieldLabel name="radio-0" key="plugins.generic.objectsForReview.editor.objectForReview.noAnswer"}<br/>
			{elseif $metadata->getMetadataType() == REVIEW_OBJECT_METADATA_TYPE_DROP_DOWN_BOX}
				<select size="1" class="selectMenu">
					<option label="{translate key="plugins.generic.objectsForReview.editor.objectForReview.chooseOption"}" value="">{translate key="plugins.generic.objectsForReview.editor.objectForReview.chooseOption"}</option>
					{assign var=possibleOptions value=$metadata->getLocalizedPossibleOptions()}
					{foreach name=options from=$possibleOptions key=optionId item=optionItem}
						<option>{$optionItem.content|escape}</option>
					{/foreach}
				</select>
			{elseif $metadata->getMetadataType() == REVIEW_OBJECT_METADATA_TYPE_LANG_DROP_DOWN_BOX}
				<select size="5" class="selectMenu" multiple="multiple">
					{html_options options=$validLanguages selected=$language}
				</select>
			{/if}
		</td>
	</tr>
	{/if}
{/foreach}
</table>
</div>

<br/>

<form id="previewReviewObjectType" method="post" action="{url op="editReviewObjectType" path=$typeId}">
	<p><input type="submit" value="{translate key="common.close"}" class="button defaultButton" /></p>
</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
{include file="common/footer.tpl"}

