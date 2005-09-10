{**
 * pdfInterstitial.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Interstitial page used to display a note about plugins
 * before sending browser directly to the PDF file
 *
 * $Id$
 *}

<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>

{assign var=escapedArticleId value=$articleId|escape:"url"}

<head>
	<meta http-equiv="Content-Type" content="text/html; charset={$defaultCharset}" />
	<title>{translate key="article.pdf.title"}</title>
	<link rel="stylesheet" href="{$baseUrl}/styles/common.css" type="text/css" />
	<link rel="stylesheet" href="{$baseUrl}/styles/articleView.css" type="text/css" />
	{foreach from=$stylesheets item=cssFile}
	<link rel="stylesheet" href="{$baseUrl}/styles/{$cssFile}" type="text/css" />
	{/foreach}
	{if $pageStyleSheet}
	<link rel="stylesheet" href="{$publicFilesDir}/{$pageStyleSheet.uploadName}" type="text/css" />
	{/if}
	<meta http-equiv="refresh" content="2;URL={$requestPageUrl}/viewFile/{$escapedArticleId}/{$galley->getGalleyId()}"/>

</head>
<body>

<div id="container">
<div id="body">
<div id="main">
<div id="content">
		<h3>{translate key="article.pdf.title"}</h3>

<p>{translate key="article.pdf.note" pdfUrl=$requestPageUrl/download/`$escapedArticleId`/`$galley->getGalleyId()`}</p>

</div>
</div>
</div>
</div>
</body>
</html>
