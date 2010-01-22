{**
 * externalFeeds.tpl
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of external feeds in plugin management.
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="plugins.generic.externalFeed.manager.feeds"}
{include file="common/header.tpl"}
{/strip}

<ul class="menu">
	<li class="current"><a href="{plugin_url path="feeds"}">{translate key="plugins.generic.externalFeed.manager.feeds"}</a></li>
	<li><a href="{plugin_url path="settings"}">{translate key="plugins.generic.externalFeed.manager.settings"}</a></li>
</ul>

<br />

{url|assign:"setup56" page="manager" op="setup" path="5"}
<p>{translate key="plugins.generic.externalFeed.manager.displayBlockInstructions" setupStep56=$setup56}</p>

<br />

<div id="feeds">

<table width="100%" class="listing">
	<tr>
		<td colspan="5" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="40%">{translate key="plugins.generic.externalFeed.manager.title"}</td>
		<td width="15%">{translate key="plugins.generic.externalFeed.manager.displayHomepage"}</td>
		<td width="25%" colspan="2">{translate key="plugins.generic.externalFeed.manager.displayBlock"}
			<table width="100%" class="nested">
				<tr valign="top">
					<td width="50%" style="padding: 0 4px 0 0; font-size: 1.0em">{translate key="plugins.generic.externalFeed.manager.displayBlockAll"}</td>
					<td width="50%" style="padding: 0 4px 0 0; font-size: 1.0em">{translate key="plugins.generic.externalFeed.manager.displayBlockHomepage"}</td>
				</tr>
			</table>
		</td>
		<td width="15%">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td colspan="5" class="headseparator">&nbsp;</td>
	</tr>
{iterate from=feeds item=feed}
	<tr valign="top" id="externalFeed-{$feed->getId()}" class="data">
		<td class="drag">{$feed->getLocalizedTitle()}</td>
		<td class="drag">{if $feed->getDisplayHomepage()}<img src="{$baseUrl}/templates/images/icons/checked.gif" alt="{translate key="plugins.generic.externalFeed.manager.displayHomepage.altText"}"/>{else}<img src="{$baseUrl}/templates/images/icons/unchecked.gif" alt="{translate key="plugins.generic.externalFeed.manager.noDisplayHomepage.altText"}"/>{/if}</td>
		<td class="drag">{if $feed->getDisplayBlock() == $smarty.const.EXTERNAL_FEED_DISPLAY_BLOCK_ALL}<img src="{$baseUrl}/templates/images/icons/checked.gif" alt="{translate key="plugins.generic.externalFeed.manager.displayBlockAll.altText"}"/>{else}<img src="{$baseUrl}/templates/images/icons/unchecked.gif" alt="{translate key="plugins.generic.externalFeed.manager.noDisplayBlockAll.altText"}"/>{/if}</td>
		<td class="drag">{if $feed->getDisplayBlock() == $smarty.const.EXTERNAL_FEED_DISPLAY_BLOCK_HOMEPAGE}<img src="{$baseUrl}/templates/images/icons/checked.gif" alt="{translate key="plugins.generic.externalFeed.manager.displayBlockHomepage.altText"}"/>{else}<img src="{$baseUrl}/templates/images/icons/unchecked.gif" alt="{translate key="plugins.generic.externalFeed.manager.noDisplayBlockHomepage.altText"}"/>{/if}</td>
		<td><a href="{plugin_url path="move" id=$feed->getId() dir=u}" class="action">&uarr;</a>&nbsp;<a href="{plugin_url path="move" id=$feed->getId() dir=d}" class="action">&darr;</a>&nbsp;|&nbsp;<a href="{plugin_url path="edit" id=$feed->getId()}" class="action">{translate key="common.edit"}</a>&nbsp;|&nbsp;<a href="{plugin_url path="delete" id=$feed->getId()}" onclick="return confirm('{translate|escape:"jsparam" key="plugins.generic.externalFeed.manager.confirmDelete"}')" class="action">{translate key="common.delete"}</a></td>
	</tr>
	<tr>
		<td colspan="5" class="{if $feeds->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $feeds->wasEmpty()}
	<tr>
		<td colspan="5" class="nodata">{translate key="plugins.generic.externalFeed.manager.noneCreated"}</td>
	</tr>
	<tr>
		<td colspan="5" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td colspan="2" align="left">{page_info iterator=$feeds}</td>
		<td colspan="3" align="right">{page_links anchor="feeds" name="feeds" iterator=$feeds}</td>
	</tr>
{/if}
</table>

<a href="{plugin_url path="create"}" class="action">{translate key="plugins.generic.externalFeed.manager.create"}</a>
</div>

{include file="common/footer.tpl"}
