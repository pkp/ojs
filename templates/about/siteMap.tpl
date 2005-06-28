{**
 * siteMap.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * About the Journal / Site Map.
 *
 * TODO: Show the site map.
 *
 * $Id$
 *}

{assign var="pageTitle" value="about.siteMap"}
{include file="common/header.tpl"}

<ul class="plain">
<li>
	<a href="{$indexUrl}">{translate key="navigation.home"}</a><br/>
	<ul class="plain">
	{if $journals|@count>1 && !$currentJournal}
		{foreach from=$journals item=journal}
			<li><a href="{$indexUrl}/{$journal->getPath()}/about/siteMap">{$journal->getTitle()}</a></li>
		{/foreach}
	{else}
		{if $journals|@count==1}
			{assign var=currentJournal value=$journals[0]}
		{else}
			<li><a href="{$indexUrl}/index/about/siteMap">{translate key="journal.journals"}</a><br/>
			<ul class="plain">
			{assign var=onlyOneJournal value=1}
		{/if}

		{assign var="jBase" value=`$indexUrl`/`$currentJournal->getPath()`}
		<li><a href="{$jBase}">{$currentJournal->getTitle()}</a><br/>
			<ul class="plain">
				<li><a href="{$jBase}/about">{translate key="navigation.about"}</a></li>
				<li>
					{if $isUserLoggedIn}
						<li><a href="{$jBase}/user">{translate key="navigation.userHome"}</a></li>
					{else}
						<li><a href="{$jBase}/login">{translate key="navigation.login"}</a></li>
						<li><a href="{$jBase}/register">{translate key="navigation.register"}</a></li>
					{/if}
					<li><a href="{$jBase}/search">{translate key="navigation.search"}</a></li>
					<li>{translate key="issue.issues"}<br/>
						<ul class="plain">
							<li><a href="{$jBase}/issue/current">{translate key="journal.currentIssue"}</a></li>
							<li><a href="{$jBase}/issue/archive">{translate key="navigation.archives"}</a></li>
						</ul>
					</li>
				</li>
				{foreach from=$currentJournal->getSetting('navItems') item=navItem}
					<li><a href="{if $navItem.isAbsolute}{$navItem.url}{else}{$pageUrl}{$navItem.url}{/if}">{if $navItem.isLiteral}{$navItem.name}{else}{translate key=$navItem.name}{/if}</a></li>
				{/foreach}
			</ul>
		</li>	
		{if $onlyOneJournal}</ul></li>{/if}

	{/if}
	</ul>
</li>
<li><a href="http://pkp.sfu.ca/ojs">{translate key="common.openJournalSystems"}</a></li>
<li><a href="javascript:openHelp('{$indexUrl}/index/help')">{translate key="help.help"}</a></li>
</ul>

{include file="common/footer.tpl"}
