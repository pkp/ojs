{**
 * pdfInterstitial.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
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

<head>
	<meta http-equiv="Content-Type" content="text/html; charset={$defaultCharset}" />
	<title>{translate key="article.pdf.title"}</title>

	<link rel="stylesheet" href="{$baseUrl}/styles/common.css" type="text/css" />
	<link rel="stylesheet" href="{$baseUrl}/styles/articleView.css" type="text/css" />

	{foreach from=$stylesheets item=cssUrl}
		<link rel="stylesheet" href="{$cssUrl}" type="text/css" />
	{/foreach}

	<meta http-equiv="refresh" content="2;URL={url op="viewFile" path=$articleId|to_array:$galley->getGalleyId()}"/>

	{$additionalHeadData}
</head>
<body>

<div id="container">
<div id="body">
<div id="main">
<div id="content">
		<h3>{translate key="article.pdf.title"}</h3>

{url|assign:"url" op="download" path=$articleId|to_array:$galley->getGalleyId()}
<p>{translate key="article.pdf.note" url=$url}</p>

{if $pageFooter}
<br /><br />
{$pageFooter}
{/if}
{call_hook name="Templates::Article::PdfInterstitial::PageFooter"}
</div>
</div>
</div>
</div>
</body>
</html>
