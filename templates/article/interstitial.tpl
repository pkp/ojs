{**
 * interstitial.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Interstitial page used to display a note about plugins
 * before sending browser directly to the PDF file
 *
 * $Id$
 *}

<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset={$defaultCharset}" />
	<title>{translate key="article.pdf.title"}</title>
	<link rel="stylesheet" href="{$baseUrl}/styles/common.css" type="text/css" />

{literal}
<script type="text/javascript">

var timerId = 0;

function timerFunc() {
	window.location = document.links[0].href;
}

function loadHandler() {
	timerId = setTimeout('timerFunc()', 1500);
	return true;
}

window.onload = loadHandler;

</script>
{/literal}

</head>
<body>

<div class="container">
<div class="body">
<div class="main">
<div class="content">

<!-- This is a dummy link for the javascript to pick up.
     It MUST be the first link on the page. -->
<a href="{$requestPageUrl}/viewFile/{$articleId}/{$galley->getFileId()}"></a>
<h2>{translate key="article.pdf.title"}</h2>

<p>{translate key="article.pdf.note" onClick="clearTimeout(timerId)" pdfUrl=$requestPageUrl/download/`$articleId`/`$galley->getFileId()`}</p>

</div>
</div>
</div>
</div>
</body>
</html>
