{**
 * sidebar.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Common site sidebar menu.
 *
 * $Id$
 *}

<div class="block">
	<a href="http://www.pkp.ubc.ca/ojs/" id="developedBy">{translate key="common.openJournalSystems"}</a>
</div>

<div class="block">
	<a href="javascript:openHelp('{get_help_id key="$pageId.default" url="true"}')">{translate key="navigation.journalHelp"}</a>
</div>

{if $sidebarTemplate}
	{include file=$sidebarTemplate}
{/if}
	
<div class="block">
	<span class="blockTitle">{translate key="navigation.user"}</span>
	{if $isUserLoggedIn}
	{translate key="navigation.loggedInAs"}<br />
	<strong>{$loggedInUsername}</strong>
	
	<ul>
		<li><a href="{$pageUrl}/user/profile">{translate key="navigation.myProfile"}</a></li>
		<li><a href="{$pageUrl}/login/signOut">{translate key="navigation.signOut"}</a></li>
	{if $userSession->getSessionVar('signedInAs')}
		<li><a href="{$pageUrl}/manager/signOutAsUser">{translate key="manager.people.signOutAsUser"}</a></li>
	{/if}
	</ul>
	{else}
	<form method="post" action="{$pageUrl}/login/signIn">
	<table>
	<tr>
		<td><label for="username">{translate key="user.username"}</label></td>
		<td><input type="text" id="username" name="username" value="" size="12" maxlength="32" class="textField" /></td>
	</tr>
	<tr>
		<td><label for="password">{translate key="user.password"}</label></td>
		<td><input type="password" id="password" name="password" value="{$password|escape}" size="12" maxlength="32" class="textField" /></td>
	</tr>
	<tr>
		<td colspan="2"><input type="checkbox" id="remember" name="remember" value="1" /> <label for="remember">{translate key="user.login.rememberMe"}</label></td>
	</tr>
	<tr>
		<td><input type="submit" value="{translate key="user.signIn"}" class="button" /></td>
	</tr>
	</table>
	</form>
	{/if}
</div>

{if $enableLanguageToggle}
<div class="block">
	<span class="blockTitle">{translate key="common.language"}</span>
	<form>
		<select size="1" onchange="location.href='{if $languageToggleNoUser}{$currentUrl}{if strstr($currentUrl, '?')}&{else}?{/if}setLocale={else}{$pageUrl}/user/setLocale/{/if}'+this.options[this.selectedIndex].value" class="selectMenu">{html_options options=$languageToggleLocales selected=$currentLocale}</select>
	</form>
</div>
{/if}
	
<div class="block">
	<span class="blockTitle">{translate key="navigation.journalContent"}</span>
	
	<span class="blockSubtitle">{translate key="navigation.search"}</span>
	<form method="get" action="{$pageUrl}/search">
	<table>
	<tr>
		<td><input type="text" id="search" name="search" size="15" maxlength="255" value="" class="textField" /></td>
	</tr>
	<tr>
		<td><select name="searchField" size="1" class="selectMenu">
			<option value="all">{translate key="search.allFields"}</option>
			<option value="author">{translate key="search.author"}</option>
			<option value="title">{translate key="search.title"}</option>
			<option value="abstract">{translate key="search.abstract"}</option>
			<option value="keywords">{translate key="search.indexTerms"}</option>
		</select></td>
	</tr>
	<tr>
		<td><input type="submit" value="{translate key="common.search"}" class="button" /></td>
	</tr>
	</table>
	</form>
	
	<br />
	
	{if $currentJournal}
	<span class="blockSubtitle">{translate key="navigation.browse"}</span>
	<ul>
		{** FIXME **}
		<li><a href="{$pageUrl}">{translate key="navigation.browseByIssue"}</a></li>
		<li><a href="{$pageUrl}">{translate key="navigation.browseByAuthor"}</a></li>
		<li><a href="{$pageUrl}">{translate key="navigation.browseByTitle"}</a></li>
		{if $hasOtherJournals}
		<li><a href="{$indexUrl}">{translate key="navigation.otherJournals"}</a></li>
		{/if}
	</ul>
	{/if}
</div>

<div class="block">
	<span class="blockTitle">{translate key="navigation.info"}</span>
	<ul>
		{** FIXME **}
		<li><a href="{$pageUrl}">{translate key="navigation.infoForReaders"}</a></li>
		<li><a href="{$pageUrl}">{translate key="navigation.infoForAuthors"}</a></li>
		<li><a href="{$pageUrl}">{translate key="navigation.infoForLibrarians"}</a></li>		
	</ul>
</div>
