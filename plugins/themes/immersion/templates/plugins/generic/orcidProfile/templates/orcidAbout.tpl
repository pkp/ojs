{**
 * plugins/generic/orcidProfile/templates/orcidVerify.tpl
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Copyright (c) 2018-2020 University Library Heidelberg
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Page template to display from the OrcidHandler to show ORCID verification success or failure.
 *}
{include file="frontend/components/header.tpl"}

<main class="container main__content" id="immersion_content_main">
	<div class="row">
		<div class="offset-md-1 col-md-10 offset-lg-2 col-lg-8">
			<header class="main__header">
				<h1 class="main__title">
					<span>{translate key="plugins.generic.orcidProfile.about.title"}</span>
				</h1>
			</header>
			<div class="content-body">
				<p class="description">
					{translate key="plugins.generic.orcidProfile.about.orcidExplanation"}
				</p>
				<h3>{translate key="plugins.generic.orcidProfile.about.howAndWhy.title"}</h3>
				<p class="description">
					{translate key="plugins.generic.orcidProfile.about.howAndWhy"}
				</p>
				<h3>{translate key="plugins.generic.orcidProfile.about.display.title"}</h3>
				<p class="description">
					{translate key="plugins.generic.orcidProfile.about.display"}
				</p>
			</div>
		</div>
	</div>
</main>

{include file="frontend/components/footer.tpl"}
