{**
 * versions.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * RTAdmin version list
 *
 * $Id$
 *}

{assign var="pageTitle" value="rt.researchTools"}
{include file="common/header.tpl"}

<h3>{translate key="rt.admin.versions"}</h3>

<table class="listing" width="100%">
	<tr><td class="headseparator" colspan="3">&nbsp;</td></tr>
	<tr valign="top">
		<td class="heading" width="50%">{translate key="common.title"}</td>
		<td class="heading" width="30%">{translate key="rt.admin.versions.locale"}</td>
		<td class="heading" width="20%">&nbsp;</td>
	</tr>
	<tr><td class="headseparator" colspan="3">&nbsp;</td></tr>
	{foreach from=$versions item=version}
		<tr valign="top">
			<td>{$version->getTitle()}</td>
			<td>{$version->getLocale()}</td>
		</tr>
	{foreachelse}
		<tr valign="top">
			<td class="nodata" colspan="5">{translate key="common.none"}</td>
		</tr>
	{/foreach}
</table>

{include file="common/footer.tpl"}
