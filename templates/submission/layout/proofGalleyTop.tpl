{**
 * proofGalleyTop.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Top frame for galley proofing.
 *
 * $Id$
 *}

<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset={$defaultCharset}" />
	<title>{translate key=$pageTitle}</title>
	<link rel="stylesheet" href="{$baseUrl}/styles/default.css" type="text/css" />
</head>
<body>
	<table width="100%" height="100%">
		<tr class="submissionRow">
			<td class="submissionBox" align="center">
				<a href="{$requestPageUrl}/submissionEditing/{$articleId}" target="_top">{translate key="submission.backToSubmissionEditing"}</a>
			</td>
		</tr>
	</table>
</body>
</html>
