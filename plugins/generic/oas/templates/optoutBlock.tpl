{**
 * plugins/generic/oas/templates/facetsBlock.tpl
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Faceted search results navigation block.
 *}
{if $oasDisplayPrivacyInfo}
	<div class="block plugins_generic_oas_optout" id="oasOptout">
		<span class="blockTitle">{translate key="plugins.generic.oas.optout.title"}</span>
		
		<p>{translate key="plugins.generic.oas.optout.shortDesc" privacyInfo=$privacyInfoUrl}</p>
	</div>
{/if}