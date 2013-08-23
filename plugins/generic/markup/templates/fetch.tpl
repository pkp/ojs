{**
 * plugins/gateways/markup/templates/fetch.tpl
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Atom feed template, returns document.zip manifest if all is well, or error message.
 *
 *}
<?xml version="1.0" encoding="{$defaultCharset|escape}"?>
<feed xmlns="http://www.w3.org/2005/Atom">
	<id>{$selfUrl|escape}</id>
	<title type="text">{translate key="plugins.generic.markup.archive.portalTitle"}</title>
	<updated>{$dateUpdated|date_format:"%Y-%m-%dT%T%z"|regex_replace:"/00$/":":00"}</updated>
	<generator uri="http://pkp.sfu.ca/ojs/" >PKP Open Journal Systems Server</generator>
	<entry>
		<title>{translate key="plugins.generic.markup.archive.portalSubTitle"}</title>
		<summary>{$description}</summary>
	</entry>
</feed>
