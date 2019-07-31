{**
 * plugins/generic/metadataExport/templates/info.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 *
 *}

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="{$currentLocale|replace:"_":"-"}" xml:lang="{$currentLocale|replace:"_":"-"}">
	<head>
		<title></title>
		<meta http-equiv="Content-Type" content="text/html; charset={$defaultCharset|escape}" />
		<meta name="description" content="" />
		<meta name="keywords" content="" />

		<link rel="stylesheet" href="{$baseUrl}/lib/pkp/styles/common.css" type="text/css" />
		<link rel="stylesheet" href="{$baseUrl}/styles/common.css" type="text/css" />
		<link rel="stylesheet" href="{$baseUrl}/lib/pkp/styles/rt.css" type="text/css" />
	</head>
	<body>
		<div id="container">
			<div id="header">
				<h1>{translate key="plugins.generic.metadataExport.infoHeader"}&nbsp;<em>{translate key="plugins.generic.metadataExport.displayName"}</em></h1>
			</div>
			<div style="text-align: left; padding: 10px">
				<p>{translate key="plugins.generic.metadataExport.infoGeneral"}</p>
				<p>{translate key="plugins.generic.metadataExport.infoCopyright"}</p>
			</div>
		</div>
		<input type="button" onclick="window.close()" value="{translate key="common.close"}" class="button defaultButton"  style="cursor: pointer"/>
	</body>
</html>