{**
 * step5.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 5 of journal setup.
 *
 * $Id$
 *}
{assign var="pageTitle" value="manager.setup.customizingTheLook"}
{include file="manager/setup/setupHeader.tpl"}

<script type="text/javascript">
{literal}
<!--

// Swap the given entries in the select field.
function swapEntries(field, i, j) {
	var tmpLabel = field.options[j].label;
	var tmpVal = field.options[j].value;
	var tmpText = field.options[j].text;
	var tmpSel = field.options[j].selected;
	field.options[j].label = field.options[i].label;
	field.options[j].value = field.options[i].value;
	field.options[j].text = field.options[i].text;
	field.options[j].selected = field.options[i].selected;
	field.options[i].label = tmpLabel;
	field.options[i].value = tmpVal;
	field.options[i].text = tmpText;
	field.options[i].selected = tmpSel;
}

// Move selected items up in the given select list.
function moveUp(field) {
	var i;
	for (i=0; i<field.length; i++) {
		if (field.options[i].selected == true && i>0) {
			swapEntries(field, i-1, i);
		}
	}
}

// Move selected items down in the given select list.
function moveDown(field) {
	var i;
	var max = field.length - 1;
	for (i = max; i >= 0; i--) {
		if(field.options[i].selected == true && i < max) {
			swapEntries(field, i+1, i);
		}
	}
}

// Move selected items from select list a to select list b.
function jumpList(a, b) {
	var i;
	for (i=0; i<a.options.length; i++) {
		if (a.options[i].selected == true) {
			bMax = b.options.length;
			b.options[bMax] = new Option(a.options[i].text);
			b.options[bMax].value = a.options[i].value;
			a.options[i] = null;
			i--;
		}
	}
}

function prepBlockFields() {
	var i;
	var theForm = document.setupForm;

	theForm.elements["blockSelectLeft"].value = "";
	for (i=0; i<theForm.blockSelectLeftWidget.options.length; i++) {
		theForm.blockSelectLeft.value += theForm.blockSelectLeftWidget.options[i].value + " ";
	}

	theForm.blockSelectRight.value = "";
	for (i=0; i<theForm.blockSelectRightWidget.options.length; i++) {
		theForm.blockSelectRight.value += theForm.blockSelectRightWidget.options[i].value + " ";
	}

	theForm.blockUnselected.value = "";
	for (i=0; i<theForm.blockUnselectedWidget.options.length; i++) {
		theForm.blockUnselected.value += theForm.blockUnselectedWidget.options[i].value + " ";
	}
	return true;
}

// -->
{/literal}
</script>

<form name="setupForm" method="post" action="{url op="saveSetup" path="5"}" enctype="multipart/form-data">
{include file="common/formErrors.tpl"}

{if count($formLocales) > 1}
<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="formLocale" required="true" key="common.language"}</td>
		<td width="80%" class="value">
			{url|assign:"setupFormUrl" op="setup" path="5"}
			{form_language_chooser form="setupForm" url=$setupFormUrl}
		</td>
	</tr>
</table>
{/if}

<h3>5.1 {translate key="manager.setup.journalHomepageHeader"}</h3>

<p>{translate key="manager.setup.journalHomepageHeaderDescription"}</p>

<h4>{translate key="manager.setup.journalTitle"}</h4>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label"><input type="radio" name="homeHeaderTitleType[{$formLocale|escape}]" id="homeHeaderTitleType-0" value="0"{if not $homeHeaderTitleType[$formLocale]} checked="checked"{/if} /> {fieldLabel name="homeHeaderTitleType-0" key="manager.setup.useTextTitle"}</td>
		<td width="80%" class="value"><input type="text" name="homeHeaderTitle[{$formLocale|escape}]" value="{$homeHeaderTitle[$formLocale]|escape}" size="40" maxlength="255" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label"><input type="radio" name="homeHeaderTitleType[{$formLocale|escape}]" id="homeHeaderTitleType-1" value="1"{if $homeHeaderTitleType[$formLocale]} checked="checked"{/if} /> {fieldLabel name="homeHeaderTitleType-1" key="manager.setup.useImageTitle"}</td>
		<td width="80%" class="value"><input type="file" name="homeHeaderTitleImage" class="uploadField" /> <input type="submit" name="uploadHomeHeaderTitleImage" value="{translate key="common.upload"}" class="button" /></td>
	</tr>
</table>

{if $homeHeaderTitleImage[$formLocale]}
{translate key="common.fileName"}: {$homeHeaderTitleImage[$formLocale].name|escape} {$homeHeaderTitleImage[$formLocale].dateUploaded|date_format:$datetimeFormatShort} <input type="submit" name="deleteHomeHeaderTitleImage" value="{translate key="common.delete"}" class="button" />
<br />
<img src="{$publicFilesDir}/{$homeHeaderTitleImage[$formLocale].uploadName|escape:"url"}" width="{$homeHeaderTitleImage[$formLocale].width|escape}" height="{$homeHeaderTitleImage[$formLocale].height|escape}" style="border: 0;" alt="" />
{/if}

<h4>{translate key="manager.setup.journalLogo"}</h4>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{translate key="manager.setup.useImageLogo"}</td>
		<td width="80%" class="value"><input type="file" name="homeHeaderLogoImage" class="uploadField" /> <input type="submit" name="uploadHomeHeaderLogoImage" value="{translate key="common.upload"}" class="button" /></td>
	</tr>
</table>

{if $homeHeaderLogoImage[$formLocale]}
{translate key="common.fileName"}: {$homeHeaderLogoImage[$formLocale].name|escape} {$homeHeaderLogoImage[$formLocale].dateUploaded|date_format:$datetimeFormatShort} <input type="submit" name="deleteHomeHeaderLogoImage" value="{translate key="common.delete"}" class="button" />
<br />
<img src="{$publicFilesDir}/{$homeHeaderLogoImage[$formLocale].uploadName|escape:"url"}" width="{$homeHeaderLogoImage[$formLocale].width|escape}" height="{$homeHeaderLogoImage[$formLocale].height|escape}" style="border: 0;" alt="" />
{/if}

<div class="separator"></div>


<h3>5.2 {translate key="manager.setup.journalHomepageContent"}</h3>

<p>{translate key="manager.setup.journalHomepageContentDescription"}</p>

<h4>{translate key="manager.setup.journalDescription"}</h4>

<p>{translate key="manager.setup.journalDescriptionDescription"}</p>

<p><textarea id="description" name="description[{$formLocale|escape}]" rows="3" cols="60" class="textArea">{$description[$formLocale]|escape}</textarea></p>

<h4>{translate key="manager.setup.homepageImage"}</h4>

<p>{translate key="manager.setup.homepageImageDescription"}</p>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{translate key="manager.setup.homepageImage"}</td>
		<td width="80%" class="value"><input type="file" name="homepageImage" class="uploadField" /> <input type="submit" name="uploadHomepageImage" value="{translate key="common.upload"}" class="button" /></td>
	</tr>
</table>

{if $homepageImage[$formLocale]}
{translate key="common.fileName"}: {$homepageImage[$formLocale].name|escape} {$homepageImage[$formLocale].dateUploaded|date_format:$datetimeFormatShort} <input type="submit" name="deleteHomepageImage" value="{translate key="common.delete"}" class="button" />
<br />
<img src="{$publicFilesDir}/{$homepageImage[$formLocale].uploadName|escape:"url"}" width="{$homepageImage[$formLocale].width|escape}" height="{$homepageImage[$formLocale].height|escape}" style="border: 0;" alt="" />
{/if}

<h4>{translate key="manager.setup.currentIssue"}</h4>

<table width="100%" class="data">
	<tr valign="top">
		<td width="5%" class="label"><input type="checkbox" name="displayCurrentIssue" id="displayCurrentIssue" value="1" {if $displayCurrentIssue} checked="checked"{/if} /></td>
		<td width="95%" class="value"><label for="displayCurrentIssue">{translate key="manager.setup.displayCurrentIssue"}</label></td>
	</tr>
</table>


<h4>{translate key="manager.setup.additionalContent"}</h4>

<p>{translate key="manager.setup.additionalContentDescription"}</p>

<p><textarea name="additionalHomeContent[{$formLocale|escape}]" id="additionalHomeContent" rows="12" cols="60" class="textArea">{$additionalHomeContent[$formLocale]|escape}</textarea></p>


<div class="separator"></div>


<h3>5.3 {translate key="manager.setup.journalPageHeader"}</h3>

<p>{translate key="manager.setup.journalPageHeaderDescription"}</p>

<h4>{translate key="manager.setup.journalTitle"}</h4>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label"><input type="radio" name="pageHeaderTitleType[{$formLocale|escape}]" id="pageHeaderTitleType-0" value="0"{if not $pageHeaderTitleType[$formLocale]} checked="checked"{/if} /> {fieldLabel name="pageHeaderTitleType-0" key="manager.setup.useTextTitle"}</td>
		<td width="80%" class="value"><input type="text" name="pageHeaderTitle[{$formLocale|escape}]" value="{$pageHeaderTitle[$formLocale]|escape}" size="40" maxlength="255" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label"><input type="radio" name="pageHeaderTitleType[{$formLocale|escape}]" id="pageHeaderTitleType-1" value="1"{if $pageHeaderTitleType[$formLocale]} checked="checked"{/if} /> {fieldLabel name="pageHeaderTitleType-1" key="manager.setup.useImageTitle"}</td>
		<td width="80%" class="value"><input type="file" name="pageHeaderTitleImage" class="uploadField" /> <input type="submit" name="uploadPageHeaderTitleImage" value="{translate key="common.upload"}" class="button" /></td>
	</tr>
</table>

{if $pageHeaderTitleImage[$formLocale]}
{translate key="common.fileName"}: {$pageHeaderTitleImage[$formLocale].name|escape} {$pageHeaderTitleImage[$formLocale].dateUploaded|date_format:$datetimeFormatShort} <input type="submit" name="deletePageHeaderTitleImage" value="{translate key="common.delete"}" class="button" />
<br />
<img src="{$publicFilesDir}/{$pageHeaderTitleImage[$formLocale].uploadName|escape:"url"}" width="{$pageHeaderTitleImage[$formLocale].width|escape}" height="{$pageHeaderTitleImage[$formLocale].height|escape}" style="border: 0;" alt="" />
{/if}

<h4>{translate key="manager.setup.journalLogo"}</h4>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{translate key="manager.setup.useImageLogo"}</td>
		<td width="80%" class="value"><input type="file" name="pageHeaderLogoImage" class="uploadField" /> <input type="submit" name="uploadPageHeaderLogoImage" value="{translate key="common.upload"}" class="button" /></td>
	</tr>
</table>

{if $pageHeaderLogoImage[$formLocale]}
{translate key="common.fileName"}: {$pageHeaderLogoImage[$formLocale].name|escape} {$pageHeaderLogoImage[$formLocale].dateUploaded|date_format:$datetimeFormatShort} <input type="submit" name="deletePageHeaderLogoImage" value="{translate key="common.delete"}" class="button" />
<br />
<img src="{$publicFilesDir}/{$pageHeaderLogoImage[$formLocale].uploadName|escape:"url"}" width="{$pageHeaderLogoImage[$formLocale].width|escape}" height="{$pageHeaderLogoImage[$formLocale].height|escape}" style="border: 0;" alt="" />
{/if}

<h4>{translate key="manager.setup.alternateHeader"}</h4>

<p>{translate key="manager.setup.alternateHeaderDescription"}</p>

<p><textarea name="journalPageHeader[{$formLocale|escape}]" id="journalPageHeader" rows="12" cols="60" class="textArea">{$journalPageHeader[$formLocale]|escape}</textarea></p>


<div class="separator"></div>


<h3>5.4 {translate key="manager.setup.journalPageFooter"}</h3>

<p>{translate key="manager.setup.journalPageFooterDescription"}</p>

<p><textarea name="journalPageFooter[{$formLocale|escape}]" id="journalPageFooter" rows="12" cols="60" class="textArea">{$journalPageFooter[$formLocale]|escape}</textarea></p>


<div class="separator"></div>


<h3>5.5 {translate key="manager.setup.navigationBar"}</h3>

<p>{translate key="manager.setup.itemsDescription"}</p>

<table width="100%" class="data">
{foreach name=navItems[$formLocale] from=$navItems key=navItemId item=navItem}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="navItems-$navItemId-name" key="manager.setup.labelName"}</td>
		<td width="80%" class="value">
			<input type="text" name="navItems[{$formLocale|escape}][{$navItemId}][name]" id="navItems-{$navItemId}-name" value="{$navItem.name|escape}" size="30" maxlength="90" class="textField" /> <input type="submit" name="delNavItem[{$navItemId}]" value="{translate key="common.delete"}" class="button" />
			<table width="100%">
				<tr valign="top">
					<td width="5%"><input type="checkbox" name="navItems[{$formLocale|escape}][{$navItemId}][isLiteral]" id="navItems-{$navItemId}-isLiteral" value="1"{if $navItem.isLiteral} checked="checked"{/if} /></td>
					<td width="95%"><label for="navItems-{$navItemId}-isLiteral">{translate key="manager.setup.navItemIsLiteral"}</label></td>
				</tr>
			</table>
		</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="navItems-$navItemId-url" key="common.url"}</td>
		<td width="80%" class="value">
			<input type="text" name="navItems[{$formLocale|escape}][{$navItemId}][url]" id="navItems-{$navItemId}-url" value="{$navItem.url|escape}" size="60" maxlength="255" class="textField" />
			<table width="100%">
				<tr valign="top">
					<td width="5%"><input type="checkbox" name="navItems[{$formLocale|escape}][{$navItemId}][isAbsolute]" id="navItems-{$navItemId}-isAbsolute" value="1"{if $navItem.isAbsolute} checked="checked"{/if} /></td>
					<td width="95%"><label for="navItems-{$navItemId}-isAbsolute">{translate key="manager.setup.navItemIsAbsolute"}</label></td>
				</tr>
			</table>
		</td>
	</tr>
	{if !$smarty.foreach.navItems.last}
	<tr valign="top">
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
{foreachelse}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="navItems-0-name" key="manager.setup.labelName"}</td>
		<td width="80%" class="value">
			<input type="text" name="navItems[{$formLocale|escape}][0][name]" id="navItems-0-name" size="30" maxlength="90" class="textField" />
			<table width="100%">
				<tr valign="top">
					<td width="5%"><input type="checkbox" name="navItems[{$formLocale|escape}][0][isLiteral]" id="navItems-0-isLiteral" value="1" /></td>
					<td width="95%"><label for="navItems-0-isLiteral">{translate key="manager.setup.navItemIsLiteral"}</label></td>
				</tr>
			</table>
		</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="navItems-0-url" key="common.url"}</td>
		<td width="80%" class="value">
			<input type="text" name="navItems[{$formLocale|escape}][0][url]" id="navItems-0-url" size="60" maxlength="255" class="textField" />
			<table width="100%">
				<tr valign="top">
					<td width="5%"><input type="checkbox" name="navItems[{$formLocale|escape}][0][isAbsolute]" id="navItems-0-isAbsolute" value="1" /></td>
					<td width="95%"><label for="navItems-0-isAbsolute">{translate key="manager.setup.navItemIsAbsolute"}</label></td>
				</tr>
			</table>
		</td>
	</tr>
{/foreach}
</table>

<p><input type="submit" name="addNavItem" value="{translate key="manager.setup.addNavItem"}" class="button" /></p>


<div class="separator"></div>


<h3>5.6 {translate key="manager.setup.journalLayout"}</h3>

<p>{translate key="manager.setup.journalLayoutDescription"}</p>

<table width="100%" class="data">
<tr>
	<td width="20%" class="label"><label for="journalTheme">{translate key="manager.setup.journalTheme"}</label></td></td>
	<td width="80%" class="value">
		<select name="journalTheme" class="selectMenu" id="journalTheme"{if empty($journalThemes)} disabled="disabled"{/if}>
			<option value="">{translate key="common.none"}</option>
			{foreach from=$journalThemes key=path item=journalThemePlugin}
				<option value="{$path|escape}"{if $path == $journalTheme} selected="selected"{/if}>{$journalThemePlugin->getDisplayName()}</option>
			{/foreach}
		</select>
	</td>
</tr>
<tr>
	<td width="20%" class="label"><label for="journalStyleSheet">{translate key="manager.setup.useJournalStyleSheet"}</label></td>
	<td width="80%" class="value"><input type="file" name="journalStyleSheet" id="journalStyleSheet" class="uploadField" /> <input type="submit" name="uploadJournalStyleSheet" value="{translate key="common.upload"}" class="button" /></td>
</tr>
</table>

{if $journalStyleSheet}
{translate key="common.fileName"}: <a href="{$publicFilesDir}/{$journalStyleSheet.uploadName|escape:"url"}" class="file">{$journalStyleSheet.name|escape}</a> {$journalStyleSheet.dateUploaded|date_format:$datetimeFormatShort} <input type="submit" name="deleteJournalStyleSheet" value="{translate key="common.delete"}" class="button" />
{/if}

<table border="0" align="center">
	<tr align="center">
		<td rowspan="2">
			{translate key="manager.setup.layout.leftSidebar"}<br/>
			<input class="button defaultButton" style="width: 130px;" type="button" value="&uarr;" onclick="moveUp(this.form.elements['blockSelectLeftWidget']);" />
			<select name="blockSelectLeftWidget" multiple size="10" class="selectMenu" style="width: 130px; height:200px" >
				{foreach from=$leftBlockPlugins item=block}
					<option value="{$block->getName()|escape}">{$block->getDisplayName()|escape}</option>
				{/foreach}
			</select><br/>
			<input class="button defaultButton" style="width: 130px;" type="button" value="&darr;" onclick="moveDown(this.form.elements['blockSelectLeftWidget']);" />
		</td>
		<td>
			<input class="button defaultButton" style="width: 30px;" type="button" value="&larr;" onclick="jumpList(this.form.elements['blockUnselectedWidget'],this.form.elements['blockSelectLeftWidget']);" />
			<input class="button defaultButton" style="width: 30px;" type="button" value="&rarr;" onclick="jumpList(this.form.elements['blockSelectLeftWidget'],this.form.elements['blockUnselectedWidget']);" />

		</td>
		<td valign="top">
			{translate key="manager.setup.layout.unselected"}<br/>
			<select name="blockUnselectedWidget" multiple size="10" class="selectMenu" style="width: 120px; height:180px;" >
				{foreach from=$disabledBlockPlugins item=block}
					<option value="{$block->getName()|escape}">{$block->getDisplayName()|escape}</option>
				{/foreach}
			</select>
		</td> 
		<td>
			<input class="button defaultButton" style="width: 30px;" type="button" value="&larr;" onclick="jumpList(this.form.elements['blockSelectRightWidget'],this.form.elements['blockUnselectedWidget']);" />
			<input class="button defaultButton" style="width: 30px;" type="button" value="&rarr;" onclick="jumpList(this.form.elements['blockUnselectedWidget'],this.form.elements['blockSelectRightWidget']);" />
		</td>
		<td rowspan="2">
			{translate key="manager.setup.layout.rightSidebar"}<br/>
			<input class="button defaultButton" style="width: 130px;" type="button" value="&uarr;" onclick="moveUp(this.form.elements['blockSelectRightWidget']);" />
			<select name="blockSelectRightWidget" multiple size="10" class="selectMenu" style="width: 130px; height:200px" >
				{foreach from=$rightBlockPlugins item=block}
					<option value="{$block->getName()|escape}">{$block->getDisplayName()|escape}</option>
				{/foreach}
			</select><br/>
			<input class="button defaultButton" style="width: 130px;" type="button" value="&darr;" onclick="moveDown(this.form.elements['blockSelectRightWidget']);" />
		</td>
	</tr>
	<tr align="center">
		<td colspan="3" valign="top" height="60px">
			<input class="button defaultButton" style="width: 190px;" type="button" value="&larr;" onclick="jumpList(this.form.elements['blockSelectRightWidget'],this.form.elements['blockSelectLeftWidget']);" />
			<input class="button defaultButton" style="width: 190px;" type="button" value="&rarr;" onclick="jumpList(this.form.elements['blockSelectLeftWidget'],this.form.elements['blockSelectRightWidget']);" />
		</td>
	</tr>
</table>
<input type="hidden" name="blockSelectLeft" value="" />
<input type="hidden" name="blockSelectRight" value="" />
<input type="hidden" name="blockUnselected" value="" />

<div class="separator"></div>

<h3>5.7 {translate key="manager.setup.information"}</h3>

<p>{translate key="manager.setup.information.description"}</p>

<h4>{translate key="manager.setup.information.forReaders"}</h4>

<p><textarea name="readerInformation[{$formLocale|escape}]" id="readerInformation" rows="12" cols="60" class="textArea">{$readerInformation[$formLocale]|escape}</textarea></p>

<h4>{translate key="manager.setup.information.forAuthors"}</h4>

<p><textarea name="authorInformation[{$formLocale|escape}]" id="authorInformation" rows="12" cols="60" class="textArea">{$authorInformation[$formLocale]|escape}</textarea></p>

<h4>{translate key="manager.setup.information.forLibrarians"}</h4>

<p><textarea name="librarianInformation[{$formLocale|escape}]" id="librarianInformation" rows="12" cols="60" class="textArea">{$librarianInformation[$formLocale]|escape}</textarea></p>


<div class="separator"></div>

<h3>5.8 {translate key="manager.setup.lists"}</h3>
<p>{translate key="manager.setup.listsDescription"}</p>
<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{translate key="manager.setup.itemsPerPage"}</td>
		<td width="80%" class="value"><input type="text" size="3" name="itemsPerPage" class="textField" value="{$itemsPerPage|escape}" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{translate key="manager.setup.numPageLinks"}</td>
		<td width="80%" class="value"><input type="text" size="3" name="numPageLinks" class="textField" value="{$numPageLinks|escape}" /></td>
	</tr>
</table>

<div class="separator"></div>

<p><input type="submit" onclick="prepBlockFields()" value="{translate key="common.saveAndContinue"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="setup" escape=false}'" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{include file="common/footer.tpl"}
