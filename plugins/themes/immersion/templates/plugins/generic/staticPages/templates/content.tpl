{**
 * templates/content.tpl
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display Static Page content
 *}
{include file="frontend/components/header.tpl" pageTitleTranslated=$title|escape}

<main class="container main__content">
	<div class="row">
		<div class="offset-md-1 col-md-10 offset-lg-2 col-lg-8">
			<header class="main__header">
				<h2 class="main__title">
					<span>{$title|escape}</span>
				</h2>
			</header>
			<div class="page">
			    {$content}
			</div>
		</div>
	</div>
</main>

{include file="frontend/components/footer.tpl"}
