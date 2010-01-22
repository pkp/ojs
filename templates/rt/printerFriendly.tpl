{**
 * printerFriendly.tpl
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Article View -- printer friendly version.
 *
 * $Id$
 *}
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>{$article->getFirstAuthor(true)|escape}</title>
	<meta http-equiv="Content-Type" content="text/html; charset={$defaultCharset|escape}" />
	<meta name="description" content="" />
	<meta name="keywords" content="" />

	<link rel="stylesheet" href="{$baseUrl}/styles/common.css" type="text/css" />
	<link rel="stylesheet" href="{$baseUrl}/styles/articleView.css" type="text/css" />

	{foreach from=$stylesheets item=cssUrl}
		<link rel="stylesheet" href="{$cssUrl}" type="text/css" />
	{/foreach}

	<script type="text/javascript" src="{$baseUrl}/js/general.js"></script>
	{$additionalHeadData}
</head>
<body>

<div id="container">

<div id="body">

<div id="main">

<h2>{$siteTitle|escape}{if $issue},&nbsp;{$issue->getIssueIdentification(false,true)|strip_unsafe_html|nl2br}{/if}</h2>

<div id="content">
{if $galley}
	{$galley->getHTMLContents()}
{else}

	<h3>{$article->getArticleTitle()|strip_unsafe_html}</h3>
	<div><em>{$article->getAuthorString()|escape}</em></div>
	{if $article->getArticleAbstract()}
		<br />
		<h4>{translate key="article.abstract"}</h4>
		<br />
		<div>{$article->getArticleAbstract()|strip_unsafe_html|nl2br}</div>
	{/if}
{/if}
</div>

</div>
</div>
</div>

<script type="text/javascript">
<!--
	window.print();
// -->
</script>

</body>
</html>
