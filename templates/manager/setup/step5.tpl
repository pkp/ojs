{**
 * step5.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 5 of journal setup.
 *
<<<<<<< step5.tpl
 * $Id$
=======
 * $Id$
>>>>>>> 1.7
 *}

{assign var="pageTitle" value="manager.setup.journalSetup"}
{assign var="currentUrl" value="$pageUrl/manager/setup"}
{include file="common/header.tpl"}

<div><a href="{$pageUrl}/manager/setup/4">&lt;&lt; {translate key="manager.setup.previousStep"}</a> | <span class="disabledText">{translate key="manager.setup.nextStep"} &gt;&gt;</span></div>

<br />
<div class="subTitle">{translate key="manager.setup.stepNumber" step=5}: {translate key="manager.setup.customizingTheLook"}</div>

<br />

<form method="post" action="{$pageUrl}/manager/saveSetup/5" enctype="multipart/form-data">
{include file="common/formErrors.tpl"}

<div class="formSectionTitle">5.1 {translate key="manager.setup.journalHomepageHeader"}</div>
<div class="formSection">
<div class="formSectionDesc">{translate key="manager.setup.journalHomepageHeaderDescription"}</div>
<div class="formSubSectionTitle">{translate key="manager.setup.journalTitle"}</div>
<table class="form">
<tr>
	<td class="formLabel"><input type="radio" name="headerTitleType" value="0"{if not $headerTitleType} checked="checked"{/if} /></td>
	<td class="formLabelRight">{translate key="manager.setup.useTextTitle"}: <input type="text" name="journalHeaderTitle" value="{$journalHeaderTitle|escape}" size="30" maxlength="255" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel"><input type="radio" name="headerTitleType" value="1"{if $headerTitleType} checked="checked"{/if} /></td>
	<td class="formLabelRight">{translate key="manager.setup.useImageTitle"}: <input type="file" name="journalHeaderTitleImage" class="textField" /><input type="submit" name="uploadJournalHeaderTitleImage" value="{translate key="common.upload"}"/></td>
</tr>
{**}
{if $alternateLocale1}
<tr>
	<td class="formLabel"><input type="radio" name="headerTitleTypeAlt1" value="0"{if not $headerTitleTypeAlt1} checked="checked"{/if} /></td>
	<td class="formLabelRight">{translate key="manager.setup.useTextTitle"}: <input type="text" name="journalHeaderTitleAlt1" value="{$journalHeaderTitleAlt1|escape}" size="30" maxlength="255" class="textField" /></td>
</tr>
{/if}
{if $alternateLocale2}
<tr>
	<td class="formLabel"><input type="radio" name="headerTitleTypeAlt2" value="0"{if not $headerTitleTypeAlt2} checked="checked"{/if} /></td>
	<td class="formLabelRight">{translate key="manager.setup.useTextTitle"}: <input type="text" name="journalHeaderTitleAlt2" value="{$journalHeaderTitleAlt2|escape}" size="30" maxlength="255" class="textField" /></td>
</tr>
{/if}
{**}
</table>
<div class="formSectionIndent">
{if $journalHeaderTitleImage}
<table class="infoTable">
<tr>
	<td class="infoLabel">{translate key="common.fileName"}:</td>
	<td>{$journalHeaderTitleImage.name}</td>
</tr>
<tr>
	<td class="infoLabel">{translate key="common.dateUploaded"}:</td>
	<td>{$journalHeaderTitleImage.dateUploaded}</td>
</tr>
</table>
<img src="{$publicDir}/{$journalHeaderTitleImage.uploadName}" alt="journalHeaderTitleImage.name"/>
<br />
<input type="submit" name="deleteJournalHeaderTitleImage" value="{translate key="common.delete"}" class="formButtonPlain" />
{else}
<table class="infoTable">
<tr>
	<td colspan="2" class="noResults">{translate key="manager.setup.noImageFileUploaded"}</td>
</tr>
</table>
{/if}
</div>

<div class="formSubSectionTitle">{translate key="manager.setup.journalLogo"}</div>
<div class="formSectionIndent">
<table class="form">
<tr>
	<td class="formLabel">{translate key="manager.setup.useImageLogo"}:</td>
	<td class="formField"><input type="file" name="journalHeaderLogoImage" class="textField" /><input type="submit" name="uploadJournalHeaderLogoImage" value="{translate key="common.upload"}"/></td>
</tr>
</table>
{if $journalHeaderLogoImage}
<table class="infoTable">
<tr>
	<td class="infoLabel">{translate key="common.fileName"}:</td>
	<td>{$journalHeaderLogoImage.name}</td>
</tr>
<tr>
	<td class="infoLabel">{translate key="common.dateUploaded"}:</td>
	<td>{$journalHeaderLogoImage.dateUploaded}</td>
</tr>
</table>
<img src="{$publicDir}/{$journalHeaderLogoImage.uploadName}" alt="{$journalHeaderLogoImage.name}"/>
<br />
<input type="submit" name="deleteJournalHeaderLogoImage" value="{translate key="common.delete"}" class="formButtonPlain" />
{else}
<table class="infoTable">
<tr>
	<td colspan="2" class="noResults">{translate key="manager.setup.noImageFileUploaded"}</td>
</tr>
</table>
{/if}
</div>
</div>

<br />

<div class="formSectionTitle">5.2 {translate key="manager.setup.journalHomepageContent"}</div>
<div class="formSection">
<div class="formSectionDesc">{translate key="manager.setup.journalHomepageContentDescription"}</div>
<div class="formSubSectionTitle">{translate key="manager.setup.journalDescription"}</div>
<div class="formSectionIndent">
<div class="formSectionDes">{translate key="manager.setup.journalDescriptionDescription"}</div>
<table class="form">
<tr>
	<td class="formField"><textarea name="journalDescription" wrap="virtual" rows="3" cols="60" class="textArea">{$journalDescription|escape}</textarea></td>
</tr>
</table>
</div>

<div class="formSubSectionTitle">{translate key="manager.setup.homepageImage"}</div>
<div class="formSectionIndent">
<div class="formSectionDescription">{translate key="manager.setup.homepageImageDescription"}</div>
<table class="form">
<tr>
	<td class="formLabel">{translate key="manager.setup.homepageImage"}:</td>
	<td class="formField"><input type="file" name="homepageImage" class="textField" /><input type="submit" name="uploadHomepageImage" value="{translate key="common.upload"}"/></td>
</tr>
</table>
{if $homepageImage}
<table class="infoTable">
<tr>
	<td class="infoLabel">{translate key="common.fileName"}:</td>
	<td>{$homepageImage.name}</td>
</tr>
<tr>
	<td class="infoLabel">{translate key="common.dateUploaded"}:</td>
	<td>{$homepageImage.dateUploaded}</td>
</tr>
</table>
<img src="{$publicDir}/{$homepageImage.uploadName}" alt="{$homepageImage.name}"/>
<br />
<input type="submit" name="deleteHomepageImage" value="{translate key="common.delete"}" class="formButtonPlain" />
{else}
<table class="infoTable">
<tr>
	<td colspan="2" class="noResults">{translate key="manager.setup.noImageFileUploaded"}</td>
</tr>
</table>
{/if}
</div>

<div class="formSubSectionTitle">{translate key="manager.setup.additionalContent"}</div>
<div class="formSectionIndent">
<div class="formSectionDescription">{translate key="manager.setup.additionalContentDescription"}</div>
<table class="form">
<tr>
	<td class="formField"><textarea name="additionalContent" rows="12" cols="60" class="textArea">{$additionalContent|escape}</textarea></td>
</tr>
</table>
</div>

<br />

<div class="formSectionTitle">5.3 {translate key="manager.setup.navigationBar"}</div>
<div class="formSection">
<div class="formSectionDesc">{translate key="manager.setup.itemsDescription"}</div>
{foreach name=navItems from=$navItems key=navItemId item=navItem}
<table class="form">
<tr>
	<td class="formLabel">{translate key="manager.setup.labelName"}:</td>
	<td class="formField"><input type="text" name="navItems[{$navItemId}][name]" value="{$navItem.name|escape}" size="32" maxlength="32" class="textField" />{if !$smarty.foreach.navItems.last}<input type="submit" name="delNavItem[{$navItemId}]" value="{translate key="common.delete"}" class="formButtonPlain" />{/if}</td>
</tr>
<tr>
	<td class="formLabel">{translate key="common.url"}:</td>
	<td class="formField"><input type="text" name="navItems[{$navItemId}][url]" value="{$navItem.url|escape}" size="45" maxlength="255" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel"><input type="checkbox" name="navItems[{$navItemId}][isLiteral]" value="1"{if $navItem.isLiteral} checked="checked"{/if} /></td>
	<td class="formLabelRightPlain">{translate key="manager.setup.navItemIsLiteral"}</td>
</tr>
<tr>
	<td class="formLabel"><input type="checkbox" name="navItems[{$navItemId}][isAbsolute]" value="1"{if $navItem.isAbsolute} checked="checked"{/if} /></td>
	<td class="formLabelRightPlain">{translate key="manager.setup.navItemIsAbsolute"}</td>
</tr>
</table>
{foreachelse}
<table class="form">
<tr>
	<td class="formLabel">{translate key="manager.setup.labelName"}:</td>
	<td class="formField"><input type="text" name="navItems[0][name]" size="32" maxlength="32" class="textField" /></td>
</tr>	
<tr>
	<td class="formLabel">{translate key="common.url"}:</td>
	<td class="formField"><input type="text" name="navItems[0][url]" value="" size="45" maxlength="255" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel"><input type="checkbox" name="navItems[0][isLiteral]" value="1" /></td>
	<td class="formLabelRightPlain">{translate key="manager.setup.navItemIsLiteral"}</td>
</tr>
<tr>
	<td class="formLabel"><input type="checkbox" name="navItems[0][isAbsolute]" value="1" /></td>
	<td class="formLabelRightPlain">{translate key="manager.setup.navItemIsAbsolute"}</td>
</tr>
</table>
{/foreach}
<div align="center"><input type="submit" name="addNavItem" value="{translate key="manager.setup.addNavItem"}" class="formButtonPlain" /></div>
</div>

<br />

<div class="formSectionTitle">5.4 {translate key="manager.setup.journalPageHeader"}</div>
<div class="formSection">
<div class="formSectionDesc">{translate key="manager.setup.journalPageHeaderDescription"}</div>
<div class="formSubSectionTitle">{translate key="manager.setup.journalTitle"}</div>
<table class="form">
<tr>
	<td class="formLabel"><input type="radio" name="pageHeaderTitleType" value="0"{if not $pageHeaderTitleType} checked="checked"{/if} /></td>
	<td class="formLabelRight">{translate key="manager.setup.useTextTitle"}: <input type="text" name="pageHeaderTitle" value="{$pageHeaderTitle|escape}" size="30" maxlength="255" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel"><input type="radio" name="pageHeaderTitleType" value="1"{if $pageHeaderTitleType} checked="checked"{/if} /></td>
	<td class="formLabelRight">{translate key="manager.setup.useImageTitle"}: <input type="file" name="pageHeaderTitleImage" class="textField" /><input type="submit" name="uploadPageHeaderTitleImage" value="{translate key="common.upload"}"/></td>
</tr>
</table>
<div class="formSectionIndent">
{if $pageHeaderTitleImage}
<table class="infoTable">
<tr>
	<td class="infoLabel">{translate key="common.fileName"}:</td>
	<td>{$pageHeaderTitleImage.name}</td>
</tr>
<tr>
	<td class="infoLabel">{translate key="common.dateUploaded"}:</td>
	<td>{$pageHeaderTitleImage.dateUploaded}</td>
</tr>
</table>
<img src="{$publicDir}/{$pageHeaderTitleImage.uploadName}" alt="{$publicDir}/{$pageHeaderTitleImage.uploadName}"/>
<br />
<input type="submit" name="deletePageHeaderTitleImage" value="{translate key="common.delete"}" class="formButtonPlain" />
{else}
<table class="infoTable">
<tr>
	<td colspan="2" class="noResults">{translate key="manager.setup.noImageFileUploaded"}</td>
</tr>
</table>
{/if}
</div>

<div class="formSubSectionTitle">{translate key="manager.setup.journalLogo"}</div>
<div class="formSectionIndent">
<table class="form">
<tr>
	<td class="formLabel">{translate key="manager.setup.useImageLogo"}:</td>
	<td class="formField"><input type="file" name="pageHeaderLogoImage" class="textField" /><input type="submit" name="uploadPageHeaderLogoImage" value="{translate key="common.upload"}"/></td>
</tr>
</table>
{if $pageHeaderLogoImage}
<table class="infoTable">
<tr>
	<td class="infoLabel">{translate key="common.fileName"}:</td>
	<td>{$pageHeaderLogoImage.name}</td>
</tr>
<tr>
	<td class="infoLabel">{translate key="common.dateUploaded"}:</td>
	<td>{$pageHeaderLogoImage.dateUploaded}</td>
</tr>
</table>
<img src="{$publicDir}/{$pageHeaderLogoImage.uploadName}" alt="{$publicDir}/{$pageHeaderLogoImage.uploadName}"/>
<br />
<input type="submit" name="deletePageHeaderLogoImage" value="{translate key="common.delete"}" class="formButtonPlain" />
{else}
<table class="infoTable">
<tr>
	<td colspan="2" class="noResults">{translate key="manager.setup.noImageFileUploaded"}</td>
</tr>
</table>
{/if}
</div>

<div class="formSubSectionTitle">{translate key="manager.setup.alternateHeader"}</div>
<div class="formSectionDesc">{translate key="manager.setup.alternateHeaderDescription"}</div>
<div class="formSectionIndent">
<table class="form">
<tr>
	<td class="formField"><textarea name="alternateHeader" rows="12" cols="60" class="textArea">{$alternateHeader|escape}</textarea></td>
</tr>
</table>
</div>
</div>

</br>

<div class="formSectionTitle">5.5 {translate key="manager.setup.journalPageFooter"}</div>
<div class="formSection">
<div class="formSectionDesc">{translate key="manager.setup.journalPageFooterDescription"}</div>
<table class="form">
<tr>
	<td class="formField"><textarea name="journalPageFooter" rows="12" cols="60" class="textArea">{$journalPageFooter|escape}</textarea></td>
</tr>
</table>
</div>

</br>

<div class="formSectionTitle">5.6 {translate key="manager.setup.journalStyleSheet"}</div>
<div class="formSection">
<div class="formSectionDesc">{translate key="manager.setup.journalStyleSheetDescription"}</div>
<table class="form">
<tr>
	<td class="formLabel">{translate key="manager.setup.useJournalStyleSheet"}:</td>
	<td class="formField"><input type="file" name="journalStyleSheet" class="textField" /><input type="submit" name="uploadJournalStyleSheet" value="{translate key="common.upload"}"/></td>
</tr>
</table>
{if $journalStyleSheet}
<table class="infoTable">
<tr>
	<td class="infoLabel">{translate key="common.fileName"}:</td>
	<td>{$journalStyleSheet.name}</td>
</tr>
<tr>
	<td class="infoLabel">{translate key="common.dateUploaded"}:</td>
	<td>{$journalStyleSheet.dateUploaded}</td>
</tr>
</table>
<input type="submit" name="deleteJournalStyleSheet" value="{translate key="common.delete"}" class="formButtonPlain" />
{else}
<table class="infoTable">
<tr>
	<td colspan="2" class="noResults">{translate key="manager.setup.noStyleSheetUploaded"}</td>
</tr>
</table>
{/if}
</div>

</br>

<table class="form">
<tr>
	<td></td>
	<td class="formField"><input type="submit" value="{translate key="common.save"}" class="formButton" /> <input type="button" value="{translate key="common.cancel"}" class="formButtonPlain" onclick="document.location.href='{$pageUrl}/manager/setup'" /></td>
</tr>
</table>
</form>
{include file="common/footer.tpl"}