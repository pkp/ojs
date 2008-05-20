{**
 * interstitial.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Interstitial page used to display a note
 * before downloading a file
 *
 * $Id$
 *}
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
	<meta http-equiv="Content-Type" content="text/html; charset={$defaultCharset|escape}" />
	<title>{translate key="article.nonpdf.title"}</title>

	<link rel="stylesheet" href="{$baseUrl}/styles/common.css" type="text/css" />
	<link rel="stylesheet" href="{$baseUrl}/styles/articleView.css" type="text/css" />

	{foreach from=$stylesheets item=cssUrl}
		<link rel="stylesheet" href="{$cssUrl}" type="text/css" />
	{/foreach}

	<script type="text/javascript" src="{$baseUrl}/js/general.js"></script>
	<meta http-equiv="refresh" content="2;URL={url op="download" path=$articleId|to_array:$galley->getGalleyId()}"/>
	{$additionalHeadData}
</head>
<body>

<div id="container">
<div id="body">
<div id="main">
<div id="content">
		<h3>{translate key="article.nonpdf.title"}</h3>
{url|assign:"url" op="download" path=$articleId|to_array:$galley->getGalleyId()}
<p>{translate key="article.nonpdf.note" url=$url}</p>

{if $pageFooter}
<br /><br />
{$pageFooter}
{/if}
{call_hook name="Templates::Article::Interstitial::PageFooter"}
</div>
</div>
</div>
</div>
</body>
</html>
