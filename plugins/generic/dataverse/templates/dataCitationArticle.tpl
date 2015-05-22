{**
 * plugins/generic/dataverse/templates/dataCitationArticle.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Dataverse plugin: include data citation in article landing page
 *
 *}
{if $dataCitation}
	<div class="separator"></div>
	<div id="dataCitation">
		<h4>{translate key="plugins.generic.dataverse.dataCitation"}</h4>
		<p>{$dataCitation|strip_unsafe_html}</p>
	</div>
{/if}
