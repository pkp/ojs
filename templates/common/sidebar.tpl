{**
 * sidebar.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Common site sidebar menu.
 *
 * $Id$
 *}

<div class="sidebarBlock">
<div class="sidebarBlockSubtitle"><a href="javascript:openHelp('{get_help_id key="$pageId.default" url="true"}')">{translate key="navigation.journalHelp"}</a>
<a href="javascript:openHelp('{get_help_id key="$pageId.default" url="true"}')" class="icon"><img src="{$baseUrl}/templates/images/help.gif" width="34" height="34" border="0" alt="" id="helpLogo" /></a></div>

<br />

<div class="helpBlock">
<strong>LOREM IPSUM</strong> Dolor sit amet, consectetuer adipiscing elit, sed diem nonummy nibh euismod tincidunt ut lacreet dolore magna aliguam erat volutpat.
</div>
</div>

{if $sidebarTemplate}
	{include file=$sidebarTemplate}
	<br />
{/if}

<div class="sidebarBlockTitle">{translate key="navigation.user"}</div>
<div class="sidebarBlock">
{if $isUserLoggedIn}
{translate key="navigation.loggedInAs" username="$loggedInUsername"}

<br /><br />

<ul class="sidebar">
	<li><a href="{$pageUrl}/user/profile">{translate key="navigation.myProfile"}</a></li>
	<li><a href="{$pageUrl}/login/signOut">{translate key="navigation.signOut"}</a></li>
</ul>
{else}
<form method="post" action="{$pageUrl}/login/signIn">
<table class="sidebarPlainTable">
<tr>
	<td><strong>{translate key="user.username"}</strong></td>
	<td><input type="text" name="username" value="" size="12" maxlength="32" class="textField" /></td>
</tr>
<tr>
	<td><strong>{translate key="user.password"}</strong></td>
	<td><input type="password" name="password" value="{$password|escape}" size="12" maxlength="32" class="textField" /></td>
</tr>
<tr>
	<td colspan="2"><input type="checkbox" name="remember" value="1" /> {translate key="user.login.rememberMe"}</td>
</tr>
<tr>
	<td><input type="submit" value="{translate key="user.signIn"}" class="button" /></td>
</tr>
</table>
</form>
{/if}

{if $enableLanguageToggle}
<br /><br />

<div class="sidebarBlockSubtitle">{translate key="common.language"}</div>
<form>
<table class="sidebarPlainTable">
<tr>
	<td><select onchange="location.href='{if $languageToggleNoUser}{$currentUrl}{if strstr($currentUrl, '?')}&{else}?{/if}setLocale={else}{$pageUrl}/user/setLocale/{/if}'+this.options[this.selectedIndex].value">{html_options options=$languageToggleLocales selected=$currentLocale}</select></td>
</tr>
</table>
</form>
{/if}
</div>

<br />

<div class="sidebarBlockTitle">{translate key="navigation.journalContent"}</div>
<div class="sidebarBlock">
<div class="sidebarBlockSubtitle">{translate key="navigation.search"}</div>
<form method="get" action="{$pageUrl}/search">
<table class="sidebarPlainTable">
<tr>
	<td><input type="text" name="search" size="20" maxlength="255" value="" class="textField" /></td>
</tr>
<tr>
	<td><select name="searchField" class="selectMenu">
	<option value="all">{translate key="search.allFields"}</option>
	<option value="author">{translate key="search.author"}</option>
	<option value="title">{translate key="article.title"}</option>
	<option value="abstract">{translate key="search.abstract"}</option>
	<option value="keywords">{translate key="search.indexTerms"}</option>
	</select></td>
</tr>
<tr>
	<td><input type="submit" value="{translate key="navigation.search"}" class="button" /></td>
</tr>
</table>
</form>

{if $currentJournal}
<br /><br />

<div class="sidebarBlockSubtitle">{translate key="navigation.browse"}</div>
<ul class="sidebar">
	<li><a href="{$pageUrl}">{translate key="navigation.browseByIssue"}</a></li>
	<li><a href="{$pageUrl}">{translate key="navigation.browseByAuthor"}</a></li>
	<li><a href="{$pageUrl}">{translate key="navigation.browseByTitle"}</a></li>
	{if $hasOtherJournals}
	<li><a href="{$pageUrl}">{translate key="navigation.otherJournals"}</a></li>
	{/if}
</ul>
{/if}
</div>

<br />

<div class="sidebarBlockTitle">{translate key="navigation.info"}</div>
<div class="sidebarBlock">
<ul class="sidebar">
	<li><a href="{$pageUrl}">{translate key="navigation.infoForReaders"}</a></li>
	<li><a href="{$pageUrl}">{translate key="navigation.infoForAuthors"}</a></li>
	<li><a href="{$pageUrl}">{translate key="navigation.infoForLibrarians"}</a></li>
</ul>
</div>
