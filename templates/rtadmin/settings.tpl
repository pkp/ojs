{**
 * settings.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * RT Administration settings.
 *
 * $Id$
 *}

{assign var="pageTitle" value="rt.admin.settings"}
{include file="common/header.tpl"}

<form method="post" action="{url op="saveSettings"}">

<p>{translate key="rt.admin.settings.description"}</p>

<input type="checkbox" {if $enabled}checked="checked" {/if}name="enabled" value="1" id="enabled"/>&nbsp;&nbsp;<label for="enabled">{translate key="rt.admin.settings.enableReadingTools"}</label><br/>

<div class="separator"></div>

<h3>{translate key="rt.admin.options"}</h3>
<table width="100%" class="data">
	<tr valign="top">
		<td class="label" width="3%"><input type="checkbox" name="abstract" id="abstract" {if $abstract}checked="checked" {/if}/></td>
		<td class="value" width="97%"><label for="abstract">{translate key="rt.admin.settings.abstract"}</label></td>
	</tr>
	<tr valign="top">
		<td class="label"><input type="checkbox" name="authorBio" id="authorBio" {if $authorBio}checked="checked" {/if}/></td>
		<td class="value"><label for="authorBio">{translate key="rt.admin.settings.authorBio"}</label></td>
	</tr>
	<tr valign="top">
		<td class="label"><input type="checkbox" name="captureCite" id="captureCite" {if $captureCite}checked="checked" {/if}/></td>
		<td class="value">
			<label for="captureCite">{translate key="rt.admin.settings.captureCite"}</label><br />
			<label for="bibFormat">{translate key="rt.admin.settings.bibFormat"}</label>&nbsp;&nbsp;<select name="bibFormat" id="bibFormat" size="1" class="selectMenu">{html_options options=$bibFormatOptions selected=$bibFormat}</select>
		</td>
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
		<td class="label"><input type="checkbox" name="defineTerms" id="defineTerms" {if $defineTerms}checked="checked" {/if}/></td>
		<td class="value"><label for="defineTerms">{translate key="rt.admin.settings.defineTerms"}</label></td>
	</tr>
	<tr valign="top">
		<td class="label"><input type="checkbox" name="emailOthers" id="emailOthers" {if $emailOthers}checked="checked" {/if}/></td>
		<td class="value"><label for="emailOthers">{translate key="rt.admin.settings.emailOthers"}</label></td>
	</tr>
	<tr valign="top">
		<td class="label"><input type="checkbox" name="emailAuthor" id="emailAuthor" {if $emailAuthor}checked="checked" {/if}/></td>
		<td class="value"><label for="emailAuthor">{translate key="rt.admin.settings.emailAuthor"}</label></td>
	</tr>
	<tr valign="top">
		<td class="label"><input type="checkbox" name="enableComments" id="enableComments" value="1"{if $enableComments} checked="checked"{/if} /></td>
		<td class="value"><label for="disableComments">{translate key="rt.admin.settings.addComment"}</label></td>
	</tr>
	<tr valign="top">
		<td class="label">&nbsp;</td>
		<td>
			<input type="radio" name="enableCommentsMode" id="enableCommentsMode-authenticated" value="{$commentsOptions.COMMENTS_AUTHENTICATED|escape}"{if $enableComments==$commentsOptions.COMMENTS_AUTHENTICATED} checked="checked"{/if} />&nbsp;&nbsp;<label for="enableCommentsMode-authenticated">{translate key="rt.admin.settings.addComment.authenticated"}</label><br />
			<input type="radio" name="enableCommentsMode" id="enableCommentsMode-anonymous" value="{$commentsOptions.COMMENTS_ANONYMOUS|escape}"{if $enableComments==$commentsOptions.COMMENTS_ANONYMOUS} checked="checked"{/if} />&nbsp;&nbsp;<label for="enableCommentsMode-anonymous">{translate key="rt.admin.settings.addComment.anonymous"}</label><br />
			<input type="radio" name="enableCommentsMode" id="enableCommentsMode-unauthenticated" value="{$commentsOptions.COMMENTS_UNAUTHENTICATED|escape}"{if $enableComments==$commentsOptions.COMMENTS_UNAUTHENTICATED} checked="checked"{/if} />&nbsp;&nbsp;<label for="enableCommentsMode-unauthenticated">{translate key="rt.admin.settings.addComment.unauthenticated"}</label><br />
		</td>
</table>

<div class="separator">&nbsp;</div>

<h3>{translate key="rt.admin.relatedItems"}</h3>

<label for="version">{translate key="rt.admin.settings.relatedItems"}</label>&nbsp;&nbsp;<select name="version" id="version" class="selectMenu">
<option value="">{translate key="rt.admin.settings.disableRelatedItems"}</option>
{html_options options=$versionOptions selected=$version}
</select><br/>
{url|assign:"relatedItemsLink" op="versions"}
{translate key="rt.admin.settings.relatedItemsLink" relatedItemsLink=$relatedItemsLink}<br/>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url page="rtadmin" escape=false}'" /></p>

</form>

{include file="common/footer.tpl"}
