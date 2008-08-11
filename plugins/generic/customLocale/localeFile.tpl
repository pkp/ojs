{**
 * localeFile.tpl
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Customize a specific locale file.
 *
 * $Id$
 *}
{strip}
{translate|assign:"pageTitleTranslated" key="plugins.generic.customLocale.locale" locale=$locale}
{include file="common/header.tpl"}
{/strip}

{assign var=filenameEscaped value=$filename|escape:"url"|escape:"url"}

<form name="reference">
{foreach from=referenceLocaleContents key=key item=value}<input type="hidden" name="{$key|escape}" value="{$key|escape}"/>{/foreach}
</form>

<form name="localeSearch" action="{plugin_url path="editLocaleFile" key=$locale file=$filenameEscaped  anchor="localeContents"}" method="post">
	{translate key="plugins.generic.customLocale.localeKey"}&nbsp;&nbsp;
	<input type="text" name="searchKey" class="textField" />&nbsp;&nbsp;
	<input type="submit" class="button defaultButton" onclick="document.locale.redirectUrl.value=document.localeSearch.action);document.locale.submit();return false;" value="{translate key="common.search"}" />
</form>

<br />
<p>{translate key="plugins.generic.customLocale.fileDescription"}</p>

<form name="locale" action="{plugin_url path="saveLocaleFile" key=$locale file=$filenameEscaped }" method="post">
<input type="hidden" name="redirectUrl" value="" />

<a name="localeContents"></a>

<h3>{translate key="plugins.generic.customLocale.file.edit" filename=$filename}</h3>
<table class="listing" width="100%">
	<tr><td colspan="3" class="headseparator">&nbsp;</td></tr>
	<tr class="heading" valign="bottom">
		<td width="35%">{translate key="plugins.generic.customLocale.localeKey"}</td>
		<td width="60%">{translate key="plugins.generic.customLocale.localeKeyValue"}</td>
	</tr>
	<tr><td colspan="2" class="headseparator">&nbsp;</td></tr>

{iterate from=referenceLocaleContents key=key item=referenceValue}
{assign var=filenameEscaped value=$filename|escape:"url"|escape:"url"}
	<tr valign="top"{if $key == $searchKey} class="highlight"{/if}>
		<td>{$key|escape}</td>
		<td>
			<input type="hidden" name="changes[]" value="{$key|escape}" />
			{if $localeContents != null}{assign var=value value=$localeContents.$key}{else}{assign var=value value=''}{/if}
			{if ($value|explode:"\n"|@count > 1) || (strlen($value) > 80) || ($referenceValue|explode:"\n"|@count > 1) || (strlen($referenceValue) > 80)}
				{translate key="plugins.generic.customLocale.file.reference"}<br/>
				<textarea name="junk[]" class="textArea" rows="5" cols="50" onkeypress="return (event.keyCode >= 37 && event.keyCode <= 40);">
{$referenceValue|escape}
</textarea>
				{translate key="plugins.generic.customLocale.file.custom"}<br/>
				<textarea name="changes[]" class="textArea" rows="5" cols="50">
{$value|escape}
</textarea>
			{else}
				{translate key="plugins.generic.customLocale.file.reference"}<br/>
				<input name="junk[]" class="textField" class="textField" type="text" size="50" onkeypress="return (event.keyCode >= 37 && event.keyCode <= 40);" value="{$referenceValue|escape}" /><br/>
				{translate key="plugins.generic.customLocale.file.custom"}<br/>
				<input name="changes[]" class="textField" class="textField" type="text" size="50" value="{$value|escape}" />
			{/if}
		</td>
	</tr>
	<tr>
		<td colspan="2" class="{if $referenceLocaleContents->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}

{if $referenceLocaleContents->wasEmpty()}
	<tr>
		<td colspan="2" class="nodata">{translate key="common.none"}</td>
	</tr>
	<tr>
		<td colspan="2" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td align="left">{page_info iterator=$referenceLocaleContents}</td>
		<td align="right">{page_links all_extra="onclick=\"document.locale.redirectUrl.value=this.href;this.href='javascript:document.locale.submit()';return true;\"" anchor="localeContents" name="referenceLocaleContents" iterator=$referenceLocaleContents}</td>
	</tr>
{/if}

</table>

{if $referenceLocaleContents->getPage() < $referenceLocaleContents->getPageCount()}
	<input type="submit" onclick="document.locale.redirectUrl.value='{plugin_url path="editLocaleFile" key=$locale file=$filenameEscaped referenceLocaleContentsPage=$referenceLocaleContents->getPage()+1 escape="false"}';return true;" class="button defaultButton" value="{translate key="common.saveAndContinue"}" />
{else}
	<input type="submit" onclick="document.locale.redirectUrl.value='{plugin_url path="editLocaleFile" key=$locale file=$filenameEscaped referenceLocaleContentsPage=$referenceLocaleContents->getPage() escape="false"}';return true;" class="button defaultButton" value="{translate key="common.save"}" />
{/if}

<input type="submit" onclick="document.locale.redirectUrl.value='{plugin_url path="edit" key=$locale escape="false"}';return true;" class="button" value="{translate key="common.done"}" />

<input type="button" onclick="document.location.href='{plugin_url path="edit" key=$locale escape="false"}';" class="button" value="{translate key="common.cancel"}" />

</form>

{include file="common/footer.tpl"}
