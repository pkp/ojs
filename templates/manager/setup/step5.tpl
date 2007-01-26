{**
 * step5.tpl
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 5 of journal setup.
 *
 * $Id$
 *}

{assign var="pageTitle" value="manager.setup.customizingTheLook}
{include file="manager/setup/setupHeader.tpl"}

<form method="post" action="{url op="saveSetup" path="5"}" enctype="multipart/form-data">
{include file="common/formErrors.tpl"}

<h3>5.1 {translate key="manager.setup.journalHomepageHeader"}</h3>

<p>{translate key="manager.setup.journalHomepageHeaderDescription"}</p>

<h4>{translate key="manager.setup.journalTitle"}</h4>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label"><input type="radio" name="homeHeaderTitleType" id="homeHeaderTitleType-0" value="0"{if not $homeHeaderTitleType} checked="checked"{/if} /> {fieldLabel name="homeHeaderTitleType-0" key="manager.setup.useTextTitle"}</td>
		<td width="80%" class="value"><input type="text" name="homeHeaderTitle" value="{$homeHeaderTitle|escape}" size="40" maxlength="255" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label"><input type="radio" name="homeHeaderTitleType" id="homeHeaderTitleType-1" value="1"{if $homeHeaderTitleType} checked="checked"{/if} /> {fieldLabel name="homeHeaderTitleType-1" key="manager.setup.useImageTitle"}</td>
		<td width="80%" class="value"><input type="file" name="homeHeaderTitleImage" class="uploadField" /> <input type="submit" name="uploadHomeHeaderTitleImage" value="{translate key="common.upload"}" class="button" /></td>
	</tr>
</table>

{if $homeHeaderTitleImage}
{translate key="common.fileName"}: {$homeHeaderTitleImage.name} {$homeHeaderTitleImage.dateUploaded|date_format:$datetimeFormatShort} <input type="submit" name="deleteHomeHeaderTitleImage" value="{translate key="common.delete"}" class="button" />
<br />
<img src="{$publicFilesDir}/{$homeHeaderTitleImage.uploadName}" width="{$homeHeaderTitleImage.width}" height="{$homeHeaderTitleImage.height}" border="0" alt="" />
{/if}

<h4>{translate key="manager.setup.journalLogo"}</h4>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{translate key="manager.setup.useImageLogo"}</td>
		<td width="80%" class="value"><input type="file" name="homeHeaderLogoImage" class="uploadField" /> <input type="submit" name="uploadHomeHeaderLogoImage" value="{translate key="common.upload"}" class="button" /></td>
	</tr>
</table>

{if $homeHeaderLogoImage}
{translate key="common.fileName"}: {$homeHeaderLogoImage.name} {$homeHeaderLogoImage.dateUploaded|date_format:$datetimeFormatShort} <input type="submit" name="deleteHomeHeaderLogoImage" value="{translate key="common.delete"}" class="button" />
<br />
<img src="{$publicFilesDir}/{$homeHeaderLogoImage.uploadName}" width="{$homeHeaderLogoImage.width}" height="{$homeHeaderLogoImage.height}" border="0" alt="" />
{/if}

{if $alternateLocale1}
<br />
<h4>{translate key="manager.setup.journalTitle"} ({$languageToggleLocales.$alternateLocale1})</h4>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label"><input type="radio" name="homeHeaderTitleTypeAlt1" id="homeHeaderTitleTypeAlt1-0" value="0"{if not $homeHeaderTitleTypeAlt1} checked="checked"{/if} /> {fieldLabel name="homeHeaderTitleTypeAlt1-0" key="manager.setup.useTextTitle"}</td>
		<td width="80%" class="value"><input type="text" name="homeHeaderTitleAlt1" value="{$homeHeaderTitleAlt1|escape}" size="40" maxlength="255" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label"><input type="radio" name="homeHeaderTitleTypeAlt1" id="homeHeaderTitleTypeAlt1-1" value="1"{if $homeHeaderTitleTypeAlt1} checked="checked"{/if} /> {fieldLabel name="homeHeaderTitleTypeAlt1-1" key="manager.setup.useImageTitle"}</td>
		<td width="80%" class="value"><input type="file" name="homeHeaderTitleImageAlt1" class="uploadField" /> <input type="submit" name="uploadHomeHeaderTitleImageAlt1" value="{translate key="common.upload"}" class="button" /></td>
	</tr>
</table>

{if $homeHeaderTitleImageAlt1}
{translate key="common.fileName"}: {$homeHeaderTitleImageAlt1.name} {$homeHeaderTitleImageAlt1.dateUploaded|date_format:$datetimeFormatShort} <input type="submit" name="deleteHomeHeaderTitleImageAlt1" value="{translate key="common.delete"}" class="button" />
<br />
<img src="{$publicFilesDir}/{$homeHeaderTitleImageAlt1.uploadName}" width="{$homeHeaderTitleImageAlt1.width}" height="{$homeHeaderTitleImageAlt1.height}" border="0" alt="" />
{/if}

<h4>{translate key="manager.setup.journalLogo"} ({$languageToggleLocales.$alternateLocale1})</h4>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{translate key="manager.setup.useImageLogo"}</td>
		<td width="80%" class="value"><input type="file" name="homeHeaderLogoImageAlt1" class="uploadField" /> <input type="submit" name="uploadHomeHeaderLogoImageAlt1" value="{translate key="common.upload"}" class="button" /></td>
	</tr>
</table>

{if $homeHeaderLogoImageAlt1}
{translate key="common.fileName"}: {$homeHeaderLogoImageAlt1.name} {$homeHeaderLogoImageAlt1.dateUploaded|date_format:$datetimeFormatShort} <input type="submit" name="deletehHomeHeaderLogoImageAlt1" value="{translate key="common.delete"}" class="button" />
<br />
<img src="{$publicFilesDir}/{$homeHeaderLogoImageAlt1.uploadName}" width="{$homeHeaderLogoImageAlt1.width}" height="{$homeHeaderLogoImageAlt1.height}" border="0" alt="" />
{/if}
{/if}

{if $alternateLocale2}
<br />
<h4>{translate key="manager.setup.journalTitle"} ({$languageToggleLocales.$alternateLocale2})</h4>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label"><input type="radio" name="homeHeaderTitleTypeAlt2" id="homeHeaderTitleTypeAlt2-0" value="0"{if not $homeHeaderTitleTypeAlt2} checked="checked"{/if} /> {fieldLabel name="homeHeaderTitleTypeAlt2-0" key="manager.setup.useTextTitle"}</td>
		<td width="80%" class="value"><input type="text" name="homeHeaderTitleAlt2" value="{$homeHeaderTitleAlt2|escape}" size="40" maxlength="255" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label"><input type="radio" name="homeHeaderTitleTypeAlt2" id="homeHeaderTitleTypeAlt2-1" value="1"{if $homeHeaderTitleTypeAlt2} checked="checked"{/if} /> {fieldLabel name="homeHeaderTitleTypeAlt2-1" key="manager.setup.useImageTitle"}</td>
		<td width="80%" class="value"><input type="file" name="homeHeaderTitleImageAlt2" class="uploadField" /> <input type="submit" name="uploadHomeHeaderTitleImageAlt2" value="{translate key="common.upload"}" class="button" /></td>
	</tr>
</table>

{if $homeHeaderTitleImageAlt2}
{translate key="common.fileName"}: {$homeHeaderTitleImageAlt2.name} {$homeHeaderTitleImageAlt2.dateUploaded|date_format:$datetimeFormatShort} <input type="submit" name="deleteHomeHeaderTitleImageAlt2" value="{translate key="common.delete"}" class="button" />
<br />
<img src="{$publicFilesDir}/{$homeHeaderTitleImageAlt2.uploadName}" width="{$homeHeaderTitleImageAlt2.width}" height="{$homeHeaderTitleImageAlt2.height}" border="0" alt="" />
{/if}

<h4>{translate key="manager.setup.journalLogo"} ({$languageToggleLocales.$alternateLocale2})</h4>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{translate key="manager.setup.useImageLogo"}</td>
		<td width="80%" class="value"><input type="file" name="homeHeaderLogoImageAlt2" class="uploadField" /> <input type="submit" name="uploadHomeHeaderLogoImageAlt2" value="{translate key="common.upload"}" class="button" /></td>
	</tr>
</table>

{if $homeHeaderLogoImageAlt2}
{translate key="common.fileName"}: {$homeHeaderLogoImageAlt2.name} {$homeHeaderLogoImageAlt2.dateUploaded|date_format:$datetimeFormatShort} <input type="submit" name="deletehHomeHeaderLogoImageAlt2" value="{translate key="common.delete"}" class="button" />
<br />
<img src="{$publicFilesDir}/{$homeHeaderLogoImageAlt2.uploadName}" width="{$homeHeaderLogoImageAlt2.width}" height="{$homeHeaderLogoImageAlt2.height}" border="0" alt="" />
{/if}
{/if}


<div class="separator"></div>


<h3>5.2 {translate key="manager.setup.journalHomepageContent"}</h3>

<p>{translate key="manager.setup.journalHomepageContentDescription"}</p>

<h4>{translate key="manager.setup.journalDescription"}</h4>

<p>{translate key="manager.setup.journalDescriptionDescription"}</p>

<p><textarea name="journalDescription" rows="3" cols="60" class="textArea">{$journalDescription|escape}</textarea></p>

<h4>{translate key="manager.setup.homepageImage"}</h4>

<p>{translate key="manager.setup.homepageImageDescription"}</p>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{translate key="manager.setup.homepageImage"}</td>
		<td width="80%" class="value"><input type="file" name="homepageImage" class="uploadField" /> <input type="submit" name="uploadHomepageImage" value="{translate key="common.upload"}" class="button" /></td>
	</tr>
</table>

{if $homepageImage}
{translate key="common.fileName"}: {$homepageImage.name} {$homepageImage.dateUploaded|date_format:$datetimeFormatShort} <input type="submit" name="deleteHomepageImage" value="{translate key="common.delete"}" class="button" />
<br />
<img src="{$publicFilesDir}/{$homepageImage.uploadName}" width="{$homepageImage.width}" height="{$homepageImage.height}" border="0" alt="" />
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

<p><textarea name="additionalHomeContent" id="additionalHomeContent" rows="12" cols="60" class="textArea">{$additionalHomeContent|escape}</textarea></p>


<div class="separator"></div>


<h3>5.3 {translate key="manager.setup.journalPageHeader"}</h3>

<p>{translate key="manager.setup.journalPageHeaderDescription"}</p>

<h4>{translate key="manager.setup.journalTitle"}</h4>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label"><input type="radio" name="pageHeaderTitleType" id="pageHeaderTitleType-0" value="0"{if not $pageHeaderTitleType} checked="checked"{/if} /> {fieldLabel name="pageHeaderTitleType-0" key="manager.setup.useTextTitle"}</td>
		<td width="80%" class="value"><input type="text" name="pageHeaderTitle" value="{$pageHeaderTitle|escape}" size="40" maxlength="255" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label"><input type="radio" name="pageHeaderTitleType" id="pageHeaderTitleType-1" value="1"{if $pageHeaderTitleType} checked="checked"{/if} /> {fieldLabel name="pageHeaderTitleType-1" key="manager.setup.useImageTitle"}</td>
		<td width="80%" class="value"><input type="file" name="pageHeaderTitleImage" class="uploadField" /> <input type="submit" name="uploadPageHeaderTitleImage" value="{translate key="common.upload"}" class="button" /></td>
	</tr>
</table>

{if $pageHeaderTitleImage}
{translate key="common.fileName"}: {$pageHeaderTitleImage.name} {$pageHeaderTitleImage.dateUploaded|date_format:$datetimeFormatShort} <input type="submit" name="deletePageHeaderTitleImage" value="{translate key="common.delete"}" class="button" />
<br />
<img src="{$publicFilesDir}/{$pageHeaderTitleImage.uploadName}" width="{$pageHeaderTitleImage.width}" height="{$pageHeaderTitleImage.height}" border="0" alt="" />
{/if}

<h4>{translate key="manager.setup.journalLogo"}</h4>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{translate key="manager.setup.useImageLogo"}</td>
		<td width="80%" class="value"><input type="file" name="pageHeaderLogoImage" class="uploadField" /> <input type="submit" name="uploadPageHeaderLogoImage" value="{translate key="common.upload"}" class="button" /></td>
	</tr>
</table>

{if $pageHeaderLogoImage}
{translate key="common.fileName"}: {$pageHeaderLogoImage.name} {$pageHeaderLogoImage.dateUploaded|date_format:$datetimeFormatShort} <input type="submit" name="deletePageHeaderLogoImage" value="{translate key="common.delete"}" class="button" />
<br />
<img src="{$publicFilesDir}/{$pageHeaderLogoImage.uploadName}" width="{$pageHeaderLogoImage.width}" height="{$pageHeaderLogoImage.height}" border="0" alt="" />
{/if}

{if $alternateLocale1}
<br />
<h4>{translate key="manager.setup.journalTitle"} ({$languageToggleLocales.$alternateLocale1})</h4>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label"><input type="radio" name="pageHeaderTitleTypeAlt1" id="pageHeaderTitleTypeAlt1-0" value="0"{if not $pageHeaderTitleTypeAlt1} checked="checked"{/if} /> {fieldLabel name="pageHeaderTitleTypeAlt1-0" key="manager.setup.useTextTitle"}</td>
		<td width="80%" class="value"><input type="text" name="pageHeaderTitleAlt1" value="{$pageHeaderTitleAlt1|escape}" size="40" maxlength="255" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label"><input type="radio" name="pageHeaderTitleTypeAlt1" id="pageHeaderTitleTypeAlt1-1" value="1"{if $pageHeaderTitleTypeAlt1} checked="checked"{/if} /> {fieldLabel name="pageHeaderTitleTypeAlt1-1" key="manager.setup.useImageTitle"}</td>
		<td width="80%" class="value"><input type="file" name="pageHeaderTitleImageAlt1" class="uploadField" /> <input type="submit" name="uploadPageHeaderTitleImageAlt1" value="{translate key="common.upload"}" class="button" /></td>
	</tr>
</table>

{if $pageHeaderTitleImageAlt1}
{translate key="common.fileName"}: {$pageHeaderTitleImageAlt1.name} {$pageHeaderTitleImageAlt1.dateUploaded|date_format:$datetimeFormatShort} <input type="submit" name="deletePageHeaderTitleImageAlt1" value="{translate key="common.delete"}" class="button" />
<br />
<img src="{$publicFilesDir}/{$pageHeaderTitleImageAlt1.uploadName}" width="{$pageHeaderTitleImageAlt1.width}" height="{$pageHeaderTitleImageAlt1.height}" border="0" alt="" />
{/if}

<h4>{translate key="manager.setup.journalLogo"} ({$languageToggleLocales.$alternateLocale1})</h4>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{translate key="manager.setup.useImageLogo"}</td>
		<td width="80%" class="value"><input type="file" name="pageHeaderLogoImageAlt1" class="uploadField" /> <input type="submit" name="uploadPageHeaderLogoImageAlt1" value="{translate key="common.upload"}" class="button" /></td>
	</tr>
</table>

{if $pageHeaderLogoImageAlt1}
{translate key="common.fileName"}: {$pageHeaderLogoImageAlt1.name} {$pageHeaderLogoImageAlt1.dateUploaded|date_format:$datetimeFormatShort} <input type="submit" name="deletePageHeaderLogoImageAlt1" value="{translate key="common.delete"}" class="button" />
<br />
<img src="{$publicFilesDir}/{$pageHeaderLogoImageAlt1.uploadName}" width="{$pageHeaderLogoImageAlt1.width}" height="{$pageHeaderLogoImageAlt1.height}" border="0" alt="" />
{/if}
{/if}

{if $alternateLocale2}
<br />
<h4>{translate key="manager.setup.journalTitle"} ({$languageToggleLocales.$alternateLocale2})</h4>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label"><input type="radio" name="pageHeaderTitleTypeAlt2" id="pageHeaderTitleTypeAlt2-0" value="0"{if not $pageHeaderTitleTypeAlt2} checked="checked"{/if} /> {fieldLabel name="pageHeaderTitleTypeAlt2-0" key="manager.setup.useTextTitle"}</td>
		<td width="80%" class="value"><input type="text" name="pageHeaderTitleAlt2" value="{$pageHeaderTitleAlt2|escape}" size="40" maxlength="255" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label"><input type="radio" name="pageHeaderTitleTypeAlt2" id="pageHeaderTitleTypeAlt2-1" value="1"{if $pageHeaderTitleTypeAlt2} checked="checked"{/if} /> {fieldLabel name="pageHeaderTitleTypeAlt2-1" key="manager.setup.useImageTitle"}</td>
		<td width="80%" class="value"><input type="file" name="pageHeaderTitleImageAlt2" class="uploadField" /> <input type="submit" name="uploadPageHeaderTitleImageAlt2" value="{translate key="common.upload"}" class="button" /></td>
	</tr>
</table>

{if $pageHeaderTitleImageAlt2}
{translate key="common.fileName"}: {$pageHeaderTitleImageAlt2.name} {$pageHeaderTitleImageAlt2.dateUploaded|date_format:$datetimeFormatShort} <input type="submit" name="deletePageHeaderTitleImageAlt2" value="{translate key="common.delete"}" class="button" />
<br />
<img src="{$publicFilesDir}/{$pageHeaderTitleImageAlt2.uploadName}" width="{$pageHeaderTitleImageAlt2.width}" height="{$pageHeaderTitleImageAlt2.height}" border="0" alt="" />
{/if}

<h4>{translate key="manager.setup.journalLogo"} ({$languageToggleLocales.$alternateLocale2})</h4>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{translate key="manager.setup.useImageLogo"}</td>
		<td width="80%" class="value"><input type="file" name="pageHeaderLogoImageAlt2" class="uploadField" /> <input type="submit" name="uploadPageHeaderLogoImageAlt2" value="{translate key="common.upload"}" class="button" /></td>
	</tr>
</table>

{if $pageHeaderLogoImageAlt2}
{translate key="common.fileName"}: {$pageHeaderLogoImageAlt2.name} {$pageHeaderLogoImageAlt2.dateUploaded|date_format:$datetimeFormatShort} <input type="submit" name="deletePageHeaderLogoImageAlt2" value="{translate key="common.delete"}" class="button" />
<br />
<img src="{$publicFilesDir}/{$pageHeaderLogoImageAlt2.uploadName}" width="{$pageHeaderLogoImageAlt2.width}" height="{$pageHeaderLogoImageAlt2.height}" border="0" alt="" />
{/if}
{/if}

<h4>{translate key="manager.setup.alternateHeader"}</h4>

<p>{translate key="manager.setup.alternateHeaderDescription"}</p>

<p><textarea name="journalPageHeader" id="journalPageHeader" rows="12" cols="60" class="textArea">{$journalPageHeader|escape}</textarea></p>


<div class="separator"></div>


<h3>5.4 {translate key="manager.setup.journalPageFooter"}</h3>

<p>{translate key="manager.setup.journalPageFooterDescription"}</p>

<p><textarea name="journalPageFooter" id="journalPageFooter" rows="12" cols="60" class="textArea">{$journalPageFooter|escape}</textarea></p>


<div class="separator"></div>


<h3>5.5 {translate key="manager.setup.navigationBar"}</h3>

<p>{translate key="manager.setup.itemsDescription"}</p>

<table width="100%" class="data">
{foreach name=navItems from=$navItems key=navItemId item=navItem}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="navItems-$navItemId-name" key="manager.setup.labelName"}</td>
		<td width="80%" class="value">
			<input type="text" name="navItems[{$navItemId}][name]" id="navItems-{$navItemId}-name" value="{$navItem.name|escape}" size="30" maxlength="90" class="textField" /> <input type="submit" name="delNavItem[{$navItemId}]" value="{translate key="common.delete"}" class="button" />
			<table width="100%">
				<tr valign="top">
					<td width="5%"><input type="checkbox" name="navItems[{$navItemId}][isLiteral]" id="navItems-{$navItemId}-isLiteral" value="1"{if $navItem.isLiteral} checked="checked"{/if} /></td>
					<td width="95%"><label for="navItems-{$navItemId}-isLiteral">{translate key="manager.setup.navItemIsLiteral"}</label></td>
				</tr>
			</table>
		</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="navItems-$navItemId-url" key="common.url"}</td>
		<td width="80%" class="value">
			<input type="text" name="navItems[{$navItemId}][url]" id="navItems-{$navItemId}-url" value="{$navItem.url|escape}" size="60" maxlength="255" class="textField" />
			<table width="100%">
				<tr valign="top">
					<td width="5%"><input type="checkbox" name="navItems[{$navItemId}][isAbsolute]" id="navItems-{$navItemId}-isAbsolute" value="1"{if $navItem.isAbsolute} checked="checked"{/if} /></td>
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
			<input type="text" name="navItems[0][name]" id="navItems-0-name" size="30" maxlength="90" class="textField" />
			<table width="100%">
				<tr valign="top">
					<td width="5%"><input type="checkbox" name="navItems[0][isLiteral]" id="navItems-0-isLiteral" value="1" /></td>
					<td width="95%"><label for="navItems-0-isLiteral">{translate key="manager.setup.navItemIsLiteral"}</label></td>
				</tr>
			</table>
		</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="navItems-0-url" key="common.url"}</td>
		<td width="80%" class="value">
			<input type="text" name="navItems[0][url]" id="navItems-0-url" size="60" maxlength="255" class="textField" />
			<table width="100%">
				<tr valign="top">
					<td width="5%"><input type="checkbox" name="navItems[0][isAbsolute]" id="navItems-0-isAbsolute" value="1" /></td>
					<td width="95%"><label for="navItems-0-isAbsolute">{translate key="manager.setup.navItemIsAbsolute"}</label></td>
				</tr>
			</table>
		</td>
	</tr>
{/foreach}
</table>

<p><input type="submit" name="addNavItem" value="{translate key="manager.setup.addNavItem"}" class="button" /></p>


<div class="separator"></div>


<h3>5.6 {translate key="manager.setup.journalStyleSheet"}</h3>

<p>{translate key="manager.setup.journalStyleSheetDescription"}</p>

<table width="100%" class="data">
<tr>
	<td width="20%" class="label">{translate key="manager.setup.useJournalStyleSheet"}</td>
	<td width="80%" class="value"><input type="file" name="journalStyleSheet" class="uploadField" /> <input type="submit" name="uploadJournalStyleSheet" value="{translate key="common.upload"}" class="button" /></td>
</tr>
</table>

{if $journalStyleSheet}
{translate key="common.fileName"}: <a href="{$publicFilesDir}/{$journalStyleSheet.uploadName}" class="file">{$journalStyleSheet.name}</a> {$journalStyleSheet.dateUploaded|date_format:$datetimeFormatShort} <input type="submit" name="deleteJournalStyleSheet" value="{translate key="common.delete"}" class="button" />
{/if}

<div class="separator"></div>

<h3>5.7 {translate key="manager.setup.information"}</h3>

<p>{translate key="manager.setup.information.description"}</p>

<h4>{translate key="manager.setup.information.forReaders"}</h4>

<p><textarea name="readerInformation" id="readerInformation" rows="12" cols="60" class="textArea">{$readerInformation|escape}</textarea></p>

<h4>{translate key="manager.setup.information.forAuthors"}</h4>

<p><textarea name="authorInformation" id="authorInformation" rows="12" cols="60" class="textArea">{$authorInformation|escape}</textarea></p>

<h4>{translate key="manager.setup.information.forLibrarians"}</h4>

<p><textarea name="librarianInformation" id="librarianInformation" rows="12" cols="60" class="textArea">{$librarianInformation|escape}</textarea></p>


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

<p><input type="submit" value="{translate key="common.saveAndContinue"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="setup" escape=false}'" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{include file="common/footer.tpl"}
