{**
 * step5.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 5 of journal setup.
 *
 * $Id$
 *}

{assign var="pageTitle" value="manager.setup.journalSetup"}
{assign var="currentUrl" value="$pageUrl/manager/setup"}
{include file="common/header.tpl"}

<div><a href="{$pageUrl}/manager/setup/4">&lt;&lt; {translate key="navigation.previousStep"}</a> | <span class="disabledText">{translate key="navigation.nextStep"} &gt;&gt;</span></div>

<br />

<div class="subTitle">{translate key="navigation.stepNumber" step=5}: {translate key="manager.setup.customizingTheLook"}</div>

<br />

<form method="post" action="{$pageUrl}/manager/saveSetup/5" enctype="multipart/form-data">
{include file="common/formErrors.tpl"}

<div class="formSectionTitle">5.1 {translate key="manager.setup.journalHomepageHeader"}</div>
<div class="formSection">
<div class="formSectionDesc">{translate key="manager.setup.journalHomepageHeaderDescription"}</div>
<div class="formSubSectionTitle">{translate key="manager.setup.journalTitle"}</div>
<table class="form">
<tr>
	<td class="formLabel">{translate key="manager.setup.useTextTitle"} <input type="radio" name="homeHeaderTitleType" value="0"{if not $homeHeaderTitleType} checked="checked"{/if} /></td>
	<td class="formFieldRight"><input type="text" name="homeHeaderTitle" value="{$homeHeaderTitle|escape}" size="30" maxlength="255" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">{translate key="manager.setup.useImageTitle"} <input type="radio" name="homeHeaderTitleType" value="1"{if $homeHeaderTitleType} checked="checked"{/if} /></td>
	<td class="formFieldRight"><input type="file" name="homeHeaderTitleImage" class="textField" /><input type="submit" name="uploadHomeHeaderTitleImage" value="{translate key="common.upload"}" /></td>
</tr>
</table>
<div class="formSectionIndent">
{if $homeHeaderTitleImage}
<table class="infoTable">
<tr>
	<td class="infoLabel">{translate key="common.fileName"}:</td>
	<td>{$homeHeaderTitleImage.name}</td>
</tr>
<tr>
	<td class="infoLabel">{translate key="common.dateUploaded"}:</td>
	<td>{$homeHeaderTitleImage.dateUploaded|date_format:$datetimeFormatShort}</td>
</tr>
</table>
<img src="{$publicFilesDir}/{$homeHeaderTitleImage.uploadName}" width="{$homeHeaderTitleImage.width}" height="{$homeHeaderTitleImage.height}" border="0" alt="" />
<br />
<input type="submit" name="deleteHomeHeaderTitleImage" value="{translate key="common.delete"}" class="formButtonPlain" />
{else}
<table class="infoTable">
<tr>
	<td colspan="2" class="noResults">{translate key="manager.setup.noImageFileUploaded"}</td>
</tr>
</table>
{/if}
</div>

<div class="formSubSectionTitle">{translate key="manager.setup.journalLogo"}</div>
<table class="form">
<tr>
	<td class="formLabel">{translate key="manager.setup.useImageLogo"}:</td>
	<td class="formField"><input type="file" name="homeHeaderLogoImage" class="textField" /><input type="submit" name="uploadHomeHeaderLogoImage" value="{translate key="common.upload"}" /></td>
</tr>
</table>
<div class="formSectionIndent">
{if $homeHeaderLogoImage}
<table class="infoTable">
<tr>
	<td class="infoLabel">{translate key="common.fileName"}:</td>
	<td>{$homeHeaderLogoImage.name}</td>
</tr>
<tr>
	<td class="infoLabel">{translate key="common.dateUploaded"}:</td>
	<td>{$homeHeaderLogoImage.dateUploaded|date_format:$datetimeFormatShort}</td>
</tr>
</table>
<img src="{$publicFilesDir}/{$homeHeaderLogoImage.uploadName}" width="{$homeHeaderLogoImage.width}" height="{$homeHeaderLogoImage.height}" border="0" alt="" />
<br />
<input type="submit" name="deleteHomeHeaderLogoImage" value="{translate key="common.delete"}" class="formButtonPlain" />
{else}
<table class="infoTable">
<tr>
	<td colspan="2" class="noResults">{translate key="manager.setup.noImageFileUploaded"}</td>
</tr>
</table>
{/if}
</div>

{if $alternateLocale1}
<br />
<div class="formSubSectionTitle">{translate key="manager.setup.journalTitle"} ({$languageToggleLocales.$alternateLocale1})</div>
<table class="form">
<tr>
	<td class="formLabel">{translate key="manager.setup.useTextTitle"} <input type="radio" name="homeHeaderTitleTypeAlt1" value="0"{if not $headerTitleTypeAlt1} checked="checked"{/if} /></td>
	<td class="formFieldRight"><input type="text" name="homeHeaderTitleAlt1" value="{$homeHeaderTitleAlt1|escape}" size="30" maxlength="255" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">{translate key="manager.setup.useImageTitle"} <input type="radio" name="homeHeaderTitleTypeAlt1" value="1"{if $homeHeaderTitleTypeAlt1} checked="checked"{/if} /></td>
	<td class="formFieldRight"><input type="file" name="homeHeaderTitleImageAlt1" class="textField" /><input type="submit" name="uploadHomeHeaderTitleImageAlt1" value="{translate key="common.upload"}" /></td>
</tr>
</table>
<div class="formSectionIndent">
{if $homeHeaderTitleImageAlt1}
<table class="infoTable">
<tr>
	<td class="infoLabel">{translate key="common.fileName"}:</td>
	<td>{$homeHeaderTitleImageAlt1.name}</td>
</tr>
<tr>
	<td class="infoLabel">{translate key="common.dateUploaded"}:</td>
	<td>{$homeHeaderTitleImageAlt1.dateUploaded|date_format:$datetimeFormatShort}</td>
</tr>
</table>
<img src="{$publicFilesDir}/{$homeHeaderTitleImageAlt1.uploadName}" width="{$homeHeaderTitleImageAlt1.width}" height="{$homeHeaderTitleImageAlt1.height}" border="0" alt="" />
<br />
<input type="submit" name="deleteHomeHeaderTitleImageAlt1" value="{translate key="common.delete"}" class="formButtonPlain" />
{else}
<table class="infoTable">
<tr>
	<td colspan="2" class="noResults">{translate key="manager.setup.noImageFileUploaded"}</td>
</tr>
</table>
{/if}
</div>

<div class="formSubSectionTitle">{translate key="manager.setup.journalLogo"} ({$languageToggleLocales.$alternateLocale1})</div>
<table class="form">
<tr>
	<td class="formLabel">{translate key="manager.setup.useImageLogo"}:</td>
	<td class="formField"><input type="file" name="homeHeaderLogoImageAlt1" class="textField" /><input type="submit" name="uploadHomeHeaderLogoImageAlt1" value="{translate key="common.upload"}" /></td>
</tr>
</table>
<div class="formSectionIndent">
{if $homeHeaderLogoImageAlt1}
<table class="infoTable">
<tr>
	<td class="infoLabel">{translate key="common.fileName"}:</td>
	<td>{$homeHeaderLogoImageAlt1.name}</td>
</tr>
<tr>
	<td class="infoLabel">{translate key="common.dateUploaded"}:</td>
	<td>{$homeHeaderLogoImageAlt1.dateUploaded|date_format:$datetimeFormatShort}</td>
</tr>
</table>
<img src="{$publicFilesDir}/{$homeHeaderLogoImageAlt1.uploadName}" width="{$homeHeaderLogoImageAlt1.width}" height="{$homeHeaderLogoImageAlt1.height}" border="0" alt="" />
<br />
<input type="submit" name="deleteHomeHeaderLogoImageAlt1" value="{translate key="common.delete"}" class="formButtonPlain" />
{else}
<table class="infoTable">
<tr>
	<td colspan="2" class="noResults">{translate key="manager.setup.noImageFileUploaded"}</td>
</tr>
</table>
{/if}
</div>
{/if}

{if $alternateLocale2}
<br />
<div class="formSubSectionTitle">{translate key="manager.setup.journalTitle"} ({$languageToggleLocales.$alternateLocale2})</div>
<table class="form">
<tr>
	<td class="formLabel">{translate key="manager.setup.useTextTitle"} <input type="radio" name="homeHeaderTitleTypeAlt2" value="0"{if not $headerTitleTypeAlt2} checked="checked"{/if} /></td>
	<td class="formFieldRight"><input type="text" name="homeHeaderTitleAlt2" value="{$homeHeaderTitleAlt2|escape}" size="30" maxlength="255" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">{translate key="manager.setup.useImageTitle"} <input type="radio" name="homeHeaderTitleTypeAlt2" value="1"{if $headerTitleTypeAlt2} checked="checked"{/if} /></td>
	<td class="formFieldRight"><input type="file" name="homeHeaderTitleTypeAlt2" class="textField" /><input type="submit" name="uploadHomeHeaderTitleImageAlt2" value="{translate key="common.upload"}" /></td>
</tr>
</table>
<div class="formSectionIndent">
{if $homeHeaderTitleImageAlt2}
<table class="infoTable">
<tr>
	<td class="infoLabel">{translate key="common.fileName"}:</td>
	<td>{$homeHeaderTitleImageAlt2.name}</td>
</tr>
<tr>
	<td class="infoLabel">{translate key="common.dateUploaded"}:</td>
	<td>{$homeHeaderTitleImageAlt2.dateUploaded|date_format:$datetimeFormatShort}</td>
</tr>
</table>
<img src="{$publicFilesDir}/{$homeHeaderTitleImageAlt2.uploadName}" width="{$homeHeaderTitleImageAlt2.width}" height="{$homeHeaderTitleImageAlt2.height}" border="0" alt="" />
<br />
<input type="submit" name="deleteHomeHeaderTitleImageAlt2" value="{translate key="common.delete"}" class="formButtonPlain" />
{else}
<table class="infoTable">
<tr>
	<td colspan="2" class="noResults">{translate key="manager.setup.noImageFileUploaded"}</td>
</tr>
</table>
{/if}
</div>

<div class="formSubSectionTitle">{translate key="manager.setup.journalLogo"} ({$languageToggleLocales.$alternateLocale2})</div>
<table class="form">
<tr>
	<td class="formLabel">{translate key="manager.setup.useImageLogo"}:</td>
	<td class="formField"><input type="file" name="homeHeaderLogoImageAlt2" class="textField" /><input type="submit" name="uploadHomeHeaderLogoImageAlt2" value="{translate key="common.upload"}" /></td>
</tr>
</table>
<div class="formSectionIndent">
{if $homeHeaderLogoImageAlt2}
<table class="infoTable">
<tr>
	<td class="infoLabel">{translate key="common.fileName"}:</td>
	<td>{$homeHeaderLogoImageAlt2.name}</td>
</tr>
<tr>
	<td class="infoLabel">{translate key="common.dateUploaded"}:</td>
	<td>{$homeHeaderLogoImageAlt2.dateUploaded|date_format:$datetimeFormatShort}</td>
</tr>
</table>
<img src="{$publicFilesDir}/{$homeHeaderLogoImageAlt2.uploadName}" width="{$homeHeaderLogoImageAlt2.width}" height="{$homeHeaderLogoImageAlt2.height}" border="0" alt="" />
<br />
<input type="submit" name="deleteHomeHeaderLogoImageAlt2" value="{translate key="common.delete"}" class="formButtonPlain" />
{else}
<table class="infoTable">
<tr>
	<td colspan="2" class="noResults">{translate key="manager.setup.noImageFileUploaded"}</td>
</tr>
</table>
{/if}
</div>
{/if}
</div>

<br />

<div class="formSectionTitle">5.2 {translate key="manager.setup.journalHomepageContent"}</div>
<div class="formSection">
<div class="formSectionDesc">{translate key="manager.setup.journalHomepageContentDescription"}</div>
<div class="formSubSectionTitle">{translate key="manager.setup.journalDescription"}</div>
<div class="formSectionIndent">
<div class="formSectionDesc">{translate key="manager.setup.journalDescriptionDescription"}</div>
<table class="form">
<tr>
	<td class="formField"><textarea name="journalDescription" wrap="virtual" rows="3" cols="60" class="textArea">{$journalDescription|escape}</textarea></td>
</tr>
</table>
</div>

<div class="formSubSectionTitle">{translate key="manager.setup.homepageImage"}</div>
<div class="formSectionDesc">{translate key="manager.setup.homepageImageDescription"}</div>
<table class="form">
<tr>
	<td class="formLabel">{translate key="manager.setup.homepageImage"}:</td>
	<td class="formField"><input type="file" name="homepageImage" class="textField" /><input type="submit" name="uploadHomepageImage" value="{translate key="common.upload"}" /></td>
</tr>
</table>
<div class="formSectionIndent">
{if $homepageImage}
<table class="infoTable">
<tr>
	<td class="infoLabel">{translate key="common.fileName"}:</td>
	<td>{$homepageImage.name}</td>
</tr>
<tr>
	<td class="infoLabel">{translate key="common.dateUploaded"}:</td>
	<td>{$homepageImage.dateUploaded|date_format:$datetimeFormatShort}</td>
</tr>
</table>
<img src="{$publicFilesDir}/{$homepageImage.uploadName}" width="{$homepageImage.width}" height="{$homepageImage.height}" border="0" alt="" />
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

<div class="formSubSectionTitle">{translate key="journal.currentIssue"}</div>
<div class="formSectionIndent">
<table class="form">
<tr>
	<td class="formLabel"><input type="checkbox" name="displayCurrentIssue" value="1" {if $displayCurrentIssue} checked="checked"{/if} /></td>
	<td class="formLabelRightPlain">{translate key="manager.setup.displayCurrentIssue"}</td>
</tr>
</table>
</div>

<br />

<div class="formSubSectionTitle">{translate key="manager.setup.additionalContent"}</div>
<div class="formSectionIndent">
<div class="formSectionDescription">{translate key="manager.setup.additionalContentDescription"}</div>
<table class="form">
<tr>
	<td class="formField"><textarea name="additionalHomeContent" rows="12" cols="60" class="textArea">{$additionalHomeContent|escape}</textarea></td>
</tr>
</table>
</div>
</div>

<br />

<div class="formSectionTitle">5.3 {translate key="manager.setup.journalPageHeader"}</div>
<div class="formSection">
<div class="formSectionDesc">{translate key="manager.setup.journalPageHeaderDescription"}</div>
<div class="formSubSectionTitle">{translate key="manager.setup.journalTitle"}</div>
<table class="form">
<tr>
	<td class="formLabel">{translate key="manager.setup.useTextTitle"} <input type="radio" name="pageHeaderTitleType" value="0"{if not $pageHeaderTitleType} checked="checked"{/if} /></td>
	<td class="formFieldRight"><input type="text" name="pageHeaderTitle" value="{$pageHeaderTitle|escape}" size="30" maxlength="255" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">{translate key="manager.setup.useImageTitle"} <input type="radio" name="pageHeaderTitleType" value="1"{if $pageHeaderTitleType} checked="checked"{/if} /></td>
	<td class="formFieldRight"><input type="file" name="pageHeaderTitleImage" class="textField" /><input type="submit" name="uploadPageHeaderTitleImage" value="{translate key="common.upload"}" /></td>
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
	<td>{$pageHeaderTitleImage.dateUploaded|date_format:$datetimeFormatShort}</td>
</tr>
</table>
<img src="{$publicFilesDir}/{$pageHeaderTitleImage.uploadName}" width="{$pageHeaderTitleImage.width}" height="{$pageHeaderTitleImage.height}" border="0" alt="" />
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
<table class="form">
<tr>
	<td class="formLabel">{translate key="manager.setup.useImageLogo"}:</td>
	<td class="formField"><input type="file" name="pageHeaderLogoImage" class="textField" /><input type="submit" name="uploadPageHeaderLogoImage" value="{translate key="common.upload"}" /></td>
</tr>
</table>
<div class="formSectionIndent">
{if $pageHeaderLogoImage}
<table class="infoTable">
<tr>
	<td class="infoLabel">{translate key="common.fileName"}:</td>
	<td>{$pageHeaderLogoImage.name}</td>
</tr>
<tr>
	<td class="infoLabel">{translate key="common.dateUploaded"}:</td>
	<td>{$pageHeaderLogoImage.dateUploaded|date_format:$datetimeFormatShort}</td>
</tr>
</table>
<img src="{$publicFilesDir}/{$pageHeaderLogoImage.uploadName}" width="{$pageHeaderLogoImage.width}" height="{$pageHeaderLogoImage.height}" border="0" alt="" />
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

{if $alternateLocale1}
<br />
<div class="formSubSectionTitle">{translate key="manager.setup.journalTitle"} ({$languageToggleLocales.$alternateLocale1})</div>
<table class="form">
<tr>
	<td class="formLabel">{translate key="manager.setup.useTextTitle"} <input type="radio" name="pageHeaderTitleTypeAlt1" value="0"{if not $pageHeaderTitleTypeAlt1} checked="checked"{/if} /></td>
	<td class="formFieldRight"><input type="text" name="pageHeaderTitleAlt1" value="{$pageHeaderTitleAlt1|escape}" size="30" maxlength="255" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">{translate key="manager.setup.useImageTitle"} <input type="radio" name="pageHeaderTitleTypeAlt1" value="1"{if $pageHeaderTitleTypeAlt1} checked="checked"{/if} /></td>
	<td class="formFieldRight"><input type="file" name="pageHeaderTitleImageAlt1" class="textField" /><input type="submit" name="uploadPageHeaderTitleImageAlt1" value="{translate key="common.upload"}" /></td>
</tr>
</table>
<div class="formSectionIndent">
{if $pageHeaderTitleImageAlt1}
<table class="infoTable">
<tr>
	<td class="infoLabel">{translate key="common.fileName"}:</td>
	<td>{$pageHeaderTitleImageAlt1.name}</td>
</tr>
<tr>
	<td class="infoLabel">{translate key="common.dateUploaded"}:</td>
	<td>{$pageHeaderTitleImageAlt1.dateUploaded|date_format:$datetimeFormatShort}</td>
</tr>
</table>
<img src="{$publicFilesDir}/{$pageHeaderTitleImageAlt1.uploadName}" width="{$pageHeaderTitleImageAlt1.width}" height="{$pageHeaderTitleImageAlt1.height}" border="0" alt="" />
<br />
<input type="submit" name="deletePageHeaderTitleImageAlt1" value="{translate key="common.delete"}" class="formButtonPlain" />
{else}
<table class="infoTable">
<tr>
	<td colspan="2" class="noResults">{translate key="manager.setup.noImageFileUploaded"}</td>
</tr>
</table>
{/if}
</div>

<div class="formSubSectionTitle">{translate key="manager.setup.journalLogo"} ({$languageToggleLocales.$alternateLocale1})</div>
<table class="form">
<tr>
	<td class="formLabel">{translate key="manager.setup.useImageLogo"}:</td>
	<td class="formField"><input type="file" name="pageHeaderLogoImageAlt1" class="textField" /><input type="submit" name="uploadPageHeaderLogoImageAlt1" value="{translate key="common.upload"}" /></td>
</tr>
</table>
<div class="formSectionIndent">
{if $pageHeaderLogoImageAlt1}
<table class="infoTable">
<tr>
	<td class="infoLabel">{translate key="common.fileName"}:</td>
	<td>{$pageHeaderLogoImageAlt1.name}</td>
</tr>
<tr>
	<td class="infoLabel">{translate key="common.dateUploaded"}:</td>
	<td>{$pageHeaderLogoImageAlt1.dateUploaded|date_format:$datetimeFormatShort}</td>
</tr>
</table>
<img src="{$publicFilesDir}/{$pageHeaderLogoImageAlt1.uploadName}" width="{$pageHeaderLogoImageAlt1.width}" height="{$pageHeaderLogoImageAlt1.height}" border="0" alt="" />
<br />
<input type="submit" name="deletePageHeaderLogoImageAlt1" value="{translate key="common.delete"}" class="formButtonPlain" />
{else}
<table class="infoTable">
<tr>
	<td colspan="2" class="noResults">{translate key="manager.setup.noImageFileUploaded"}</td>
</tr>
</table>
{/if}
</div>
{/if}

{if $alternateLocale2}
<br />
<div class="formSubSectionTitle">{translate key="manager.setup.journalTitle"} ({$languageToggleLocales.$alternateLocale2})</div>
<table class="form">
<tr>
	<td class="formLabel">{translate key="manager.setup.useTextTitle"} <input type="radio" name="pageHeaderTitleTypeAlt2" value="0"{if not $pageHeaderTitleTypeAlt2} checked="checked"{/if} /></td>
	<td class="formFieldRight"><input type="text" name="pageHeaderTitleAlt2" value="{$pageHeaderTitleAlt2|escape}" size="30" maxlength="255" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">{translate key="manager.setup.useImageTitle"} <input type="radio" name="pageHeaderTitleTypeAlt2" value="1"{if $pageHeaderTitleTypeAlt2} checked="checked"{/if} /></td>
	<td class="formFieldRight"><input type="file" name="pageHeaderTitleImageAlt2" class="textField" /><input type="submit" name="uploadPageHeaderTitleImageAlt2" value="{translate key="common.upload"}" /></td>
</tr>
</table>
<div class="formSectionIndent">
{if $pageHeaderTitleImageAlt2}
<table class="infoTable">
<tr>
	<td class="infoLabel">{translate key="common.fileName"}:</td>
	<td>{$pageHeaderTitleImageAlt2.name}</td>
</tr>
<tr>
	<td class="infoLabel">{translate key="common.dateUploaded"}:</td>
	<td>{$pageHeaderTitleImageAlt2.dateUploaded|date_format:$datetimeFormatShort}</td>
</tr>
</table>
<img src="{$publicFilesDir}/{$pageHeaderTitleImageAlt2.uploadName}" width="{$pageHeaderTitleImageAlt2.width}" height="{$pageHeaderTitleImageAlt2.height}" border="0" alt="" />
<br />
<input type="submit" name="deletePageHeaderTitleImageAlt2" value="{translate key="common.delete"}" class="formButtonPlain" />
{else}
<table class="infoTable">
<tr>
	<td colspan="2" class="noResults">{translate key="manager.setup.noImageFileUploaded"}</td>
</tr>
</table>
{/if}
</div>

<div class="formSubSectionTitle">{translate key="manager.setup.journalLogo"} ({$languageToggleLocales.$alternateLocale2})</div>
<table class="form">
<tr>
	<td class="formLabel">{translate key="manager.setup.useImageLogo"}:</td>
	<td class="formField"><input type="file" name="pageHeaderLogoImageAlt2" class="textField" /><input type="submit" name="uploadPageHeaderLogoImageAlt2" value="{translate key="common.upload"}" /></td>
</tr>
</table>
<div class="formSectionIndent">
{if $pageHeaderLogoImageAlt2}
<table class="infoTable">
<tr>
	<td class="infoLabel">{translate key="common.fileName"}:</td>
	<td>{$pageHeaderLogoImageAlt2.name}</td>
</tr>
<tr>
	<td class="infoLabel">{translate key="common.dateUploaded"}:</td>
	<td>{$pageHeaderLogoImageAlt2.dateUploaded|date_format:$datetimeFormatShort}</td>
</tr>
</table>
<img src="{$publicFilesDir}/{$pageHeaderLogoImageAlt2.uploadName}" width="{$pageHeaderLogoImageAlt2.width}" height="{$pageHeaderLogoImageAlt2.height}" border="0" alt="" />
<br />
<input type="submit" name="deletePageHeaderLogoImageAlt2" value="{translate key="common.delete"}" class="formButtonPlain" />
{else}
<table class="infoTable">
<tr>
	<td colspan="2" class="noResults">{translate key="manager.setup.noImageFileUploaded"}</td>
</tr>
</table>
{/if}
</div>
{/if}

<br />
<div class="formSubSectionTitle">{translate key="manager.setup.alternateHeader"}</div>
<div class="formSectionDesc">{translate key="manager.setup.alternateHeaderDescription"}</div>
<div class="formSectionIndent">
<table class="form">
<tr>
	<td class="formField"><textarea name="journalPageHeader" rows="12" cols="60" class="textArea">{$journalPageHeader|escape}</textarea></td>
</tr>
</table>
</div>
</div>

<br />

<div class="formSectionTitle">5.4 {translate key="manager.setup.journalPageFooter"}</div>
<div class="formSection">
<div class="formSectionDesc">{translate key="manager.setup.journalPageFooterDescription"}</div>
<div class="formSectionIndent">
<table class="form">
<tr>
	<td class="formField"><textarea name="journalPageFooter" rows="12" cols="60" class="textArea">{$journalPageFooter|escape}</textarea></td>
</tr>
</table>
</div>
</div>

<br />

<div class="formSectionTitle">5.5 {translate key="manager.setup.navigationBar"}</div>
<div class="formSection">
<div class="formSectionDesc">{translate key="manager.setup.itemsDescription"}</div>
{foreach name=navItems from=$navItems key=navItemId item=navItem}
<table class="form">
<tr>
	<td class="formLabel">{translate key="manager.setup.labelName"}:</td>
	<td class="formField"><input type="text" name="navItems[{$navItemId}][name]" value="{$navItem.name|escape}" size="32" maxlength="32" class="textField" />{if $smarty.foreach.navItems.total > 1}<input type="submit" name="delNavItem[{$navItemId}]" value="{translate key="common.delete"}" class="formButtonPlain" />{/if}</td>
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

<div class="formSectionTitle">5.6 {translate key="manager.setup.journalStyleSheet"}</div>
<div class="formSection">
<div class="formSectionDesc">{translate key="manager.setup.journalStyleSheetDescription"}</div>
<table class="form">
<tr>
	<td class="formLabel">{translate key="manager.setup.useJournalStyleSheet"}:</td>
	<td class="formField"><input type="file" name="journalStyleSheet" class="textField" /><input type="submit" name="uploadJournalStyleSheet" value="{translate key="common.upload"}" /></td>
</tr>
</table>
<div class="formSectionIndent">
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
</div>

<br />

<table class="form">
<tr>
	<td></td>
	<td class="formField"><input type="submit" value="{translate key="common.save"}" class="formButton" /> <input type="button" value="{translate key="common.cancel"}" class="formButtonPlain" onclick="document.location.href='{$pageUrl}/manager/setup'" /></td>
</tr>
</table>
</form>
{include file="common/footer.tpl"}
