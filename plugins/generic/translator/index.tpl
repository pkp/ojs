{**
 * plugins/generic/translator/index.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * List of operations this plugin can perform
 *
 *}
{strip}
{assign var="pageTitle" value="plugins.generic.translator.name"}
{include file="common/header.tpl"}
{/strip}

<p>{translate key="plugins.generic.translator.longdescription"}</p>
{if not $tarAvailable}
	<p><span class="formError">{translate key="plugins.generic.translator.tarMissing"}</span></p>
{/if}

<div id="locales">
<h3>{translate key="plugins.generic.translator.availableLocales"}</h3>
<table class="listing" width="100%">
	<tr><td colspan="3" class="headseparator">&nbsp;</td></tr>
	<tr class="heading" valign="bottom">
		<td width="15%">{translate key="plugins.generic.translator.localeKey"}</td>
		<td width="60%">{translate key="plugins.generic.translator.localeName"}</td>
		<td width="25%">{translate key="common.action"}</td>
	</tr>
	<tr><td colspan="3" class="headseparator">&nbsp;</td></tr>

{iterate from=locales key=localeKey item=localeName}
	<tr valign="top">
		<td>{$localeKey|escape}</td>
		<td>{$localeName|escape}</td>
		<td>
			{if $masterLocale != $localeKey}<a href="{url op="check" path=$localeKey}" class="action">{translate key="plugins.generic.translator.check"}</a>&nbsp;|&nbsp;{/if}<a href="{url op="edit" path=$localeKey}" class="action">{translate key="common.edit"}</a>{if $tarAvailable}&nbsp;|&nbsp;<a href="{url op="export" path=$localeKey}" class="action">{translate key="common.export"}</a>{/if}
		</td>
	</tr>
	<tr>
		<td colspan="3" class="{if $locales->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}

{if $locales->wasEmpty()}
	<tr>
		<td colspan="3" class="nodata">{translate key="common.none"}</td>
	</tr>
	<tr>
		<td colspan="3" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td colspan="2" align="left">{page_info iterator=$locales}</td>
		<td align="right">{page_links anchor="locales" name="locales" iterator=$locales}</td>
	</tr>
{/if}

</table>
</div>
{include file="common/footer.tpl"}
