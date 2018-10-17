{**
 * plugins/generic/externalFeed/externalFeedForm.tpl
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * External feed form under plugin management.
 *
 *}
{strip}
{if $feedId}
	{assign var="pageTitle" value="plugins.generic.externalFeed.manager.edit"}
{else}
	{assign var="pageTitle" value="plugins.generic.externalFeed.manager.create"}
{/if}
{include file="common/header.tpl"}
{/strip}

<br/>

<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#externalFeed').pkpHandler('$.pkp.controllers.form.FormHandler');
	{rdelim});
</script>
<form class="pkp_form" id="externalFeed" method="post" action="{plugin_url path="update"}">
{csrf}
{if $feedId}
<input type="hidden" name="feedId" value="{$feedId|escape}" />
{/if}

{include file="common/formErrors.tpl"}

<div id="externalFeedForm">
<table id="general" class="data">
{if count($formLocales) > 1}
	<tr>
		<td class="label" colspan="2">{fieldLabel name="formLocale" key="form.formLanguage"}</td>
		<td class="value">
			{capture assign="externalFeedFormUrl"}
				{if $feedId}{plugin_url path="edit" id=$feedId escape=false}
				{else}{plugin_url path="create" id=$feedId escape=false}
				{/if}
			{/capture}
			{form_language_chooser form="externalFeed" url=$externalFeedFormUrl}
			<span class="instruct">{translate key="form.formLanguage.description"}</span>
		</td>
	</tr>
{/if}
	<tr>
		<td class="label" colspan="2">{fieldLabel name="title" required="true" key="plugins.generic.externalFeed.form.title"}</td>
		<td class="value"><input type="text" name="title[{$formLocale|escape}]" value="{$title[$formLocale]|escape}" id="title" size="40" maxlength="120" class="textField" />
			<br />
			<span class="instruct">{translate key="plugins.generic.externalFeed.form.titleDescription"}</span>
		</td>
	</tr>
	<tr>
		<td class="label" colspan="2">{fieldLabel name="feedUrl" required="true" key="plugins.generic.externalFeed.form.feedUrl"}</td>
		<td class="value"><input type="text" name="feedUrl" id="feedUrl" value="{$feedUrl|escape}" size="30" maxlength="255" class="textField" />
			<br />
			<span class="instruct">{translate key="plugins.generic.externalFeed.form.feedUrlDescription"}</span>
		</td>
	</tr>
	<tr>
		<td colspan="3"><div class="separator">&nbsp;</div></td>
	</tr>
	<tr>
		<td class="label">{translate key="plugins.generic.externalFeed.form.display"}</td>
		<td class="label" align="right"><input type="checkbox" name="displayHomepage" id="displayHomepage" value="1" {if $displayHomepage}checked="checked" {/if}/></td>
		<td class="value">{translate key="plugins.generic.externalFeed.form.displayHomepage"}</td>
	</tr>
	<tr>
		<td class="label" align="right" colspan="2"><input type="radio" name="displayBlock" id="displayBlock-none" value="{$smarty.const.EXTERNAL_FEED_DISPLAY_BLOCK_NONE}" {if $displayBlock == $smarty.const.EXTERNAL_FEED_DISPLAY_BLOCK_NONE}checked="checked" {/if}/></td>
		<td class="value">{translate key="plugins.generic.externalFeed.form.displayBlockNone"}</td>
	</tr>
	<tr>
		<td class="label" align="right" colspan="2"><input type="radio" name="displayBlock" id="displayBlock-homepage" value="{$smarty.const.EXTERNAL_FEED_DISPLAY_BLOCK_HOMEPAGE}" {if $displayBlock == $smarty.const.EXTERNAL_FEED_DISPLAY_BLOCK_HOMEPAGE}checked="checked" {/if}/></td>
		<td class="value">{translate key="plugins.generic.externalFeed.form.displayBlockHomepage"}</td>
	</tr>
	<tr>
		<td class="label" align="right" colspan="2"><input type="radio" name="displayBlock" id="displayBlock-all" value="{$smarty.const.EXTERNAL_FEED_DISPLAY_BLOCK_ALL}" {if $displayBlock == $smarty.const.EXTERNAL_FEED_DISPLAY_BLOCK_ALL}checked="checked" {/if}/></td>
		<td class="value">{translate key="plugins.generic.externalFeed.form.displayBlockAll"}</td>
	</tr>
	<tr>
		<td class="label" align="right" colspan="2"><input type="checkbox" name="limitItems" id="limitItems" value="1" {if $limitItems}checked="checked" {/if}/></td>
		<td class="value">{translate key="plugins.generic.externalFeed.form.recentItems1"}
		<input type="text" name="recentItems" id="recentItems" value="{$recentItems|escape}" size="2" maxlength="90" class="textField" />
		{translate key="plugins.generic.externalFeed.form.recentItems2"}</td>
	</tr>
</table>
</div>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> {if not $feedId}<input type="submit" name="createAnother" value="{translate key="plugins.generic.externalFeed.form.saveAndCreateAnother"}" class="button" /> {/if}<input type="button" value="{translate key="common.cancel"}" class="button" onclick="history.go(-1);" /></p>

</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

{include file="common/footer.tpl"}
