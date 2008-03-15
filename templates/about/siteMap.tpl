{**
 * siteMap.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
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
	<a href="{url journal="index" page="index" op="index"}">{translate key="navigation.home"}</a><br/>
	<ul class="plain">
	{if $journals|@count>1 && !$currentJournal}
		{foreach from=$journals item=journal}
			<li><a href="{url journal=`$journal->getPath()` page="about" op="siteMap"}">{$journal->getJournalTitle()|escape}</a></li>
		{/foreach}
	{else}
		{if $journals|@count==1}
			{assign var=currentJournal value=$journals[0]}
		{else}
			<li><a href="{url journal="index" page="about" op="siteMap"}">{translate key="journal.journals"}</a><br/>
			<ul class="plain">
			{assign var=onlyOneJournal value=1}
		{/if}

		<li><a href="{url journal=`$currentJournal->getPath()`}">{$currentJournal->getJournalTitle()|escape}</a><br/>
			<ul class="plain">
				<li><a href="{url journal=`$currentJournal->getPath()` page="about"}">{translate key="navigation.about"}</a></li>
				<li>
					{if $isUserLoggedIn}
						<ul class="plain">
							<li><a href="{url journal=`$currentJournal->getPath()` page="user"}">{translate key="navigation.userHome"}</a><br/>
								<ul class="plain">
									{assign var=currentJournalId value=$currentJournal->getJournalId()}
									{foreach from=$rolesByJournal[$currentJournalId] item=role}
									{translate|assign:"roleName" key=$role->getRoleName()}
										<li><a href="{url journal=`$currentJournal->getPath()` page=`$role->getRolePath()`}">{$roleName|escape}</a></li>
									{/foreach}
								</ul>
							</li>
						</ul>
					{else}
						<ul class="plain">
							<li><a href="{url journal=`$currentJournal->getPath()` page="login"}">{translate key="navigation.login"}</a></li>
							<li><a href="{url journal=`$currentJournal->getPath()` page="register"}">{translate key="navigation.register"}</a></li>
						</ul>
					{/if}
				</li>
				<li><a href="{url journal=`$currentJournal->getPath()` page="search"}">{translate key="navigation.search"}</a><br />
					<ul class="plain">
						<li><a href="{url journal=`$currentJournal->getPath()` page="search" op="authors"}">{translate key="navigation.browseByAuthor"}</a></li>
						<li><a href="{url journal=`$currentJournal->getPath()` page="search" op="titles"}">{translate key="navigation.browseByTitle"}</a></li>
					</ul>
				</li>
				<li>{translate key="issue.issues"}<br/>
					<ul class="plain">
						<li><a href="{url journal=`$currentJournal->getPath()` page="issue" op="current"}">{translate key="journal.currentIssue"}</a></li>
						<li><a href="{url journal=`$currentJournal->getPath()` page="issue" op="archive"}">{translate key="navigation.archives"}</a></li>
					</ul>
				</li>
				{foreach from=$navMenuItems item=navItem}
					{if $navItem.url != '' && $navItem.name != ''}<li><a href="{if $navItem.isAbsolute}{$navItem.url|escape}{else}{url page=""}{$navItem.url|escape}{/if}">{if $navItem.isLiteral}{$navItem.name|escape}{else}{translate key=$navItem.name|escape}{/if}</a></li>{/if}
				{/foreach}
			</ul>
		</li>	
		{if $onlyOneJournal}</ul></li>{/if}

	{/if}
	</ul>
</li>
{if $isSiteAdmin}
	<li><a href="{url journal="index" page=$isSiteAdmin->getRolePath()}">{translate key=$isSiteAdmin->getRoleName()}</a></li>
{/if}
<li><a href="http://pkp.sfu.ca/ojs">{translate key="common.openJournalSystems"}</a></li>
<li><a href="javascript:openHelp('{url journal="index" page="help"}')">{translate key="help.help"}</a></li>
</ul>

{include file="common/footer.tpl"}
