{**
 * navbar.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Navigation Bar
 *
 *}

<div id="navbar">
	<ul class="menu">
		{* 20110824 BLH comment out HOME menu item link b/c we aren't using this. *}
		{*<li id="home"><a href="{url page="index"}">{translate key="navigation.home"}</a></li>*}
		

		{if $isUserLoggedIn}
			{* 20110825 BLH Added 'My Journals' link *}
			{if $hasOtherJournals}
				<li id="myJournals"><a href="{url journal="index" page="user"}">{translate key="navigation.myJournals"}</a></li>
			{/if}
			{* 20110825 BLH Replace confusing 'User Home' link with 'Journal Home' link. *}
			{* 20120424 LS Changing this to be Journal Initials*}
			{* <li id="userHome"><a href="{url page="user"}">{translate key="navigation.userHome"}</a></li> *}
			{if $currentJournal}
				{* old way{assign var="currentJournalPath" value=$currentJournal->getpath()}*}
				{assign var="currentJournalPath" value=$currentJournal->getPath()}
				{assign var="currentJournalInitials" value=$currentJournal->getJournalInitials()}
				<li id="userHome"><a href="{url journal=$currentJournalPath page="user"}">{translate key="navigation.journalInitials" currentJournalInitials=$currentJournalInitials}</a></li>
			{/if}
		{else}
			<li id="login"><a href="{url page="login"}">{translate key="navigation.login"}</a></li>
			{if !$hideRegisterLink}
				<li id="register"><a href="{url page="user" op="register"}">{translate key="navigation.register"}</a></li>
			{/if}
		{/if}{* $isUserLoggedIn *}
		
		{* 20110824 BLH moved ABOUT link - makes more sense in this order *}
		{* <li id="about"><a href="{url page="about"}">{translate key="navigation.about"}</a></li> *}
		
		{* 20110915 BLH remove "SEARCH" from top navbar *}
		{*
		{if !$currentJournal || $currentJournal->getSetting('publishingMode') != $smarty.const.PUBLISHING_MODE_NONE}
			<li id="search"><a href="{url page="search"}">{translate key="navigation.search"}</a></li>
		{/if}
		*}

		{*
		{if $currentJournal && $currentJournal->getSetting('publishingMode') != $smarty.const.PUBLISHING_MODE_NONE}
			<li id="current"><a href="{url page="issue" op="current"}">{translate key="navigation.current"}</a></li>
			<li id="archives"><a href="{url page="issue" op="archive"}">{translate key="navigation.archives"}</a></li>
		{/if}
		*}

		{if $enableAnnouncements}
			<li id="announcements"><a href="{url page="announcement"}">{translate key="announcement.announcements"}</a></li>
		{/if}{* enableAnnouncements *}

		<li id="eschol_help"><a href="https://submit.escholarship.org/help/journals/" target="_blank">HELP CENTER</a></li>
		{call_hook name="Templates::Common::Header::Navbar::CurrentJournal"}

		{foreach from=$navMenuItems item=navItem}
			{if $navItem.url != '' && $navItem.name != ''}
				<li id="navItem"><a href="{if $navItem.isAbsolute}{$navItem.url|escape}{else}{$navItem.url|escape}{/if}">{if $navItem.isLiteral}{$navItem.name|escape}{else}{translate key=$navItem.name}{/if}</a></li>
			{/if}
		{/foreach}
	</ul>
</div>

