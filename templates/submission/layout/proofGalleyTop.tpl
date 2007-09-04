{**
 * proofGalleyTop.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Top frame for galley proofing.
 *
 * $Id$
 *}
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset={$defaultCharset}" />
	<title>{translate key=$pageTitle}</title>

	<link rel="stylesheet" href="{$baseUrl}/styles/common.css" type="text/css" />
	{foreach from=$stylesheets item=cssUrl}
		<link rel="stylesheet" href="{$cssUrl}" type="text/css" />
	{/foreach}

	{$additionalHeadData}
</head>
<body>
	<table width="100%" height="100%">
		<tr>
			<td align="center">
				<a href="{url op=$backHandler path=$articleId}" target="_top">{translate key="submission.backToSubmissionEditing"}</a>
			</td>
		</tr>
	</table>
</body>
</html>
