{**
 * settings.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * RT Administration settings.
 *
 * $Id$
 *}

{assign var="pageTitle" value="rt.admin.settings}
{include file="common/header.tpl"}

<form method="post" action="{$pageUrl}/rtadmin/saveSettings">

<h3>{translate key="rt.version"}</h3>
<p>{translate key="rt.admin.versionDescription"}</p>
<label for="version">{translate key="rt.version"}</label>&nbsp;&nbsp;<select name="version" id="version" class="selectMenu">
{html_options options=$versionOptions selected=$version}
</select>

<div class="separator"></div>

<h3>{translate key="rt.admin.options"}</h3>
<table width="100%" class="data">
	<tr valign="top">
		<td class="label" width="3%"><input type="checkbox" name="captureCite" id="captureCite" {if $captureCite}checked="checked" {/if}/></td>
		<td class="value" width="97%"><label for="captureCite">{translate key="rt.admin.settings.captureCite"}</label></td>
	</tr>
	<tr valign="top">
		<td class="label"><input type="checkbox" name="viewMetadata" id="viewMetadata" {if $viewMetadata}checked="checked" {/if}/></td>
		<td class="value"><label for="viewMetadata">{translate key="rt.admin.settings.viewMetadata"}</td>
	</tr>
	<tr valign="top">
		<td class="label"><input type="checkbox" name="supplementaryFiles" id="supplementaryFiles" {if $supplementaryFiles}checked="checked" {/if}/></td>
		<td class="value"><label for="supplementaryFiles">{translate key="rt.admin.settings.supplementaryFiles"}</label></td>
	</tr>
	<tr valign="top">
		<td class="label"><input type="checkbox" name="printerFriendly" id="printerFriendly" {if $printerFriendly}checked="checked" {/if}/></td>
		<td class="value"><label for="printerFriendly">{translate key="rt.admin.settings.printerFriendly"}</label></td>
	</tr>
	<tr valign="top">
		<td class="label"><input type="checkbox" name="authorBio" id="authorBio" {if $authorBio}checked="checked" {/if}/></td>
		<td class="value"><label for="authorBio">{translate key="rt.admin.settings.authorBio"}</label></td>
	</tr>
	<tr valign="top">
		<td class="label"><input type="checkbox" name="defineTerms" id="defineTerms" {if $defineTerms}checked="checked" {/if}/></td>
		<td class="value"><label for="defineTerms">{translate key="rt.admin.settings.defineTerms"}</label></td>
	</tr>
	<tr valign="top">
		<td class="label"><input type="checkbox" name="addComment" id="addComment" {if $addComment}checked="checked" {/if}/></td>
		<td class="value"><label for="addComment">{translate key="rt.admin.settings.addComment"}</label></td>
	</tr>
	<tr valign="top">
		<td class="label"><input type="checkbox" name="emailAuthor" id="emailAuthor" {if $emailAuthor}checked="checked" {/if}/></td>
		<td class="value"><label for="emailAuthor">{translate key="rt.admin.settings.emailAuthor"}</label></td>
	</tr>
	<tr valign="top">
		<td class="label"><input type="checkbox" name="emailOthers" id="emailOthers" {if $emailOthers}checked="checked" {/if}/></td>
		<td class="value"><label for="emailOthers">{translate key="rt.admin.settings.emailOthers"}</label></td>
	</tr>
</table>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{$pageUrl}/rtadmin'" /></p>

</form>

{include file="common/footer.tpl"}
