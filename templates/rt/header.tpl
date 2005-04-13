{**
 * header.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Common header for RT pages.
 *
 * $Id$
 *}

<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title>{translate key="rt.readingTools"}</title>
	<meta http-equiv="Content-Type" content="text/html; charset={$defaultCharset}" />
	<meta name="description" content="" />
	<meta name="keywords" content="" />
	<link rel="stylesheet" href="{$baseUrl}/styles/common.css" type="text/css" />
	<link rel="stylesheet" href="{$baseUrl}/styles/help.css" type="text/css" />
	{foreach from=$stylesheets item=cssFile}
	<link rel="stylesheet" href="{$baseUrl}/styles/{$cssFile}" type="text/css" />
	{/foreach}
	<link rel="stylesheet" href="{$baseUrl}/styles/rt.css" type="text/css" />
	<script type="text/javascript" src="{$baseUrl}/js/general.js"></script>
</head>
<body>
{literal}<script type="text/javascript">if (self.blur) { self.focus(); }</script>{/literal}

{if !$pageTitleTranslated}{assign_translate var="pageTitleTranslated" key=$pageTitle}{/if}

<div id="container">
<div id="header">
<div id="headerTitle">

<table class="data" width="700">
<tr valign="middle">
	<td width="70%"><h1>{translate key="rt.readingTools"}</h1></td>
	<td width="30%">
		<a href="javascript:window.close()" class="action" style="font-size: 0.65em;">{translate key="common.closeWindow"}</a>
	</td>
</tr>
</table>

</div>
</div>

<div id="body">
<h2>{$pageTitleTranslated}</h2>
<a name="top"></a>

{literal}<script type="text/javascript">if (self.blur) { self.focus(); }</script>{/literal}

<div id="content">
<div id="main">
