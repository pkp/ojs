{**
 * proofGalley.tpl
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Proof a galley.
 *
 * $Id$
 *}
{assign var="pageTitle" value="submission.layout.viewingGalley"}
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset={$defaultCharset|escape}" />
	<title>{translate key=$pageTitle}</title>
	<link rel="stylesheet" href="{$baseUrl}/styles/common.css" type="text/css" />

	{foreach from=$stylesheets item=cssUrl}
		<link rel="stylesheet" href="{$cssUrl}" type="text/css" />
	{/foreach}

	<script type="text/javascript" src="{$baseUrl}/js/general.js"></script>
	{$additionalHeadData}
</head>
{url|assign:"galleyUrl" op="proofGalleyFile" path=$articleId|to_array:$galleyId}
<frameset rows="40,*" style="border: 0;">
	<frame src="{url op="proofGalleyTop" path=$articleId}" noresize="noresize" frameborder="0" scrolling="no" />
	<frame src="{$galleyUrl}" frameborder="0" />
<noframes>
<body>
	<table width="100%">
		<tr>
			<td align="center">
				{translate key="common.error.framesRequired" url=$galleyUrl}
			</td>
		</tr>
	</table>
</body>
</noframes>
</frameset>
</html>
