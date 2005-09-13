{**
 * view.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Article View.
 *
 * $Id$
 *}

<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html>

{assign var=escapedArticleId value=$articleId|escape:"url"}
{assign var=escapedGalleyId value=$galleyId|escape:"url"}

<head>
	<meta http-equiv="Content-Type" content="text/html; charset={$defaultCharset}" />
	<title>{$article->getFirstAuthor(true)|escape}</title>
	<link rel="stylesheet" href="{$baseUrl}/styles/common.css" type="text/css" />
	<link rel="stylesheet" href="{$baseUrl}/styles/articleView.css" type="text/css" />
	{foreach from=$stylesheets item=cssFile}
	<link rel="stylesheet" href="{$baseUrl}/styles/{$cssFile}" type="text/css" />
	{/foreach}
	{if $pageStyleSheet}
	<link rel="stylesheet" href="{$publicFilesDir}/{$pageStyleSheet.uploadName}" type="text/css" />
	{/if}
</head>
<frameset cols="*,180" frameborder="0" framespacing="0" border="0">
	<frame src="{$requestPageUrl}/view{if !$galley || $galley->isHtmlGalley()}Article{elseif $galley->isPdfGalley()}PDFInterstitial{else}DownloadInterstitial{/if}/{$escapedArticleId}/{$escapedGalleyId}" frameborder="0" />
	<frame src="{$requestPageUrl}/viewRST/{$escapedArticleId}/{$escapedGalleyId}" noresize="noresize" frameborder="0" scrolling="auto" />
<noframes>
<body>
	<table width="100%">
		<tr>
			<td align="center">
				{translate key="common.error.framesRequired" url="$requestPageUrl/viewArticle/$escapedArticleId/$escapedGalleyId"}
			</td>
		</tr>
	</table>
</body>
</noframes>
</frameset>
</html>
