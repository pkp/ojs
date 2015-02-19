{**
 * plugins/importexport/native/templates/results.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * List of operations this plugin can perform
 *}

{translate key="plugins.importexport.native.importComplete"}
<ul>
	{foreach from=$submissions item=submission}
		<li>{$submission->getLocalizedTitle()|strip_unsafe_html}</li>
	{/foreach}
</ul>
