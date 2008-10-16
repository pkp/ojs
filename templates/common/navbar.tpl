{**
 * navbar.tpl
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Navigation Bar
 *
 *}
<div id="navbar">
	<ul class="menu">
		<li><a href="{url page="index"}">{translate key="navigation.home"}</a></li>
		<li><a href="{url page="about"}">{translate key="navigation.about"}</a></li>

		{if $isUserLoggedIn}
			<li><a href="{url page="user"}">{translate key="navigation.userHome"}</a></li>
		{else}
			<li><a href="{url page="login"}">{translate key="navigation.login"}</a></li>
			<li><a href="{url page="user" op="register"}">{translate key="navigation.register"}</a></li>
		{/if}{* $isUserLoggedIn *}

		<li><a href="{url page="search"}">{translate key="navigation.search"}</a></li>

		{if $currentJournal}
			<li><a href="{url page="issue" op="current"}">{translate key="navigation.current"}</a></li>
			<li><a href="{url page="issue" op="archive"}">{translate key="navigation.archives"}</a></li>
		{/if}{* $currentJournal *}

		{if $enableAnnouncements}
			<li><a href="{url page="announcement"}">{translate key="announcement.announcements"}</a></li>
		{/if}{* enableAnnouncements *}

		{call_hook name="Templates::Common::Header::Navbar::CurrentJournal"}

		{foreach from=$navMenuItems item=navItem}
			{if $navItem.url != '' && $navItem.name != ''}
				<li><a href="{if $navItem.isAbsolute}{$navItem.url|escape}{else}{$navItem.url|escape}{/if}">{if $navItem.isLiteral}{$navItem.name|escape}{else}{translate key=$navItem.name}{/if}</a></li>
			{/if}
		{/foreach}
	</ul>
</div>