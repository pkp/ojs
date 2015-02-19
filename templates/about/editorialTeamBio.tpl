{**
 * templates/about/editorialTeamBio.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * View the biography of an editorial team member.
 *
 *}
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="{$currentLocale|replace:"_":"-"}" xml:lang="{$currentLocale|replace:"_":"-"}">
<head>
	<title>{translate key="about.editorialTeam"}</title>
	<meta http-equiv="Content-Type" content="text/html; charset={$defaultCharset|escape}" />
	<meta name="description" content="" />
	<meta name="keywords" content="" />

	<link rel="stylesheet" href="{$baseUrl}/lib/pkp/styles/common.css" type="text/css" />
	<link rel="stylesheet" href="{$baseUrl}/styles/common.css" type="text/css" />
	<link rel="stylesheet" href="{$baseUrl}/styles/compiled.css" type="text/css" />
	<link rel="stylesheet" href="{$baseUrl}/lib/pkp/styles/rt.css" type="text/css" />

	{foreach from=$stylesheets item=cssUrl}
		<link rel="stylesheet" href="{$cssUrl}" type="text/css" />
	{/foreach}

	<!-- Compiled scripts -->
	{if $useMinifiedJavaScript}
		<script type="text/javascript" src="{$baseUrl}/js/pkp.min.js"></script>
	{else}
		{include file="common/minifiedScripts.tpl"}
	{/if}

	{$additionalHeadData}
</head>
<body id="pkp-{$pageTitle|replace:'.':'-'}">
{literal}
<script type="text/javascript">
<!--
	if (self.blur) { self.focus(); }
// -->
</script>
{/literal}

{assign var=pageTitleTranslated value=$user->getFullName()|escape}
{if !$pageTitleTranslated}{translate|assign:"pageTitleTranslated" key=$pageTitle}{/if}

<div id="container">

<div id="header">
<div id="headerTitle">
<h1>{translate key="about.editorialTeam"}</h1>
</div>
</div>

<div id="body">
<div id="top"></div>

<div id="main">

{literal}
<script type="text/javascript">
<!--
	if (self.blur) { self.focus(); }
// -->
</script>
{/literal}

<div id="profilePicContent" style="float: right;">
	{assign var="profileImage" value=$user->getSetting('profileImage')}
	{if $profileImage}
		<img height="{$profileImage.height|escape}" width="{$profileImage.width|escape}" alt="{translate key="user.profile.profileImage"}" src="{$sitePublicFilesDir}/{$profileImage.uploadName}" />
	{/if}
</div>

<div id="mainContent">
<h2>{$pageTitleTranslated}</h2>

<div id="content">
<p>
	<em>{$user->getFullName()|escape}</em>
	{if $publishEmail}
		{assign_mailto var=address address=$user->getEmail()|escape}
		{icon name="mail" url=$address}
	{/if}
	<br />
	{if $user->getUrl()}<a href="{$user->getUrl()|escape:"quotes"}" target="_new">{$user->getUrl()|escape}</a><br/>{/if}
	{if $user->getLocalizedAffiliation()}{$user->getLocalizedAffiliation()|escape}{assign var=needsComma value=1}{/if}{if $country}{if $needsComma}, {/if}{$country|escape}{/if}
</p>

<p>{$user->getLocalizedBiography()|nl2br|strip_unsafe_html}</p>

<input type="button" onclick="window.close()" value="{translate key="common.close"}" class="button defaultButton" />

</div><!-- content -->
</div><!-- mainContent -->
</div><!-- main -->
</div><!-- body -->
</div><!-- container -->
</body>
</html>

