{**
 * templates/orcidVerify.tpl
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
 * Copyright (c) 2018-2019 University Library Heidelberg
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Page template to display from the OrcidProfileHandler to show ORCID verification success or failure.
 *}
{include file="frontend/components/header.tpl"}

<div class="page page_message">
	{include file="frontend/components/breadcrumbs.tpl" currentTitleKey="orcidProfile.about.title"}
	<h2>
		{translate key="orcidProfile.about.title"}
	</h2>
	<div class="description">
		{translate key="orcidProfile.about.orcidExplanation"}
	</div>
	<h3>{translate key="orcidProfile.about.howAndWhy.title"}</h3>
	{if $isMemberApi}
	<div class="description">
		{translate key="orcidProfile.about.howAndWhyMemberAPI"}
	</div>
	{else}
		{translate key="orcidProfile.about.howAndWhyPublicAPI"}
	{/if}
	<h3>{translate key="orcidProfile.about.display.title"}</h3>
	<div class="description">
		{translate key="orcidProfile.about.display"}
	</div>
</div>

{include file="frontend/components/footer.tpl"}
