{**
 * templates/frontend/objects/jats_link.tpl
 *
 * Copyright (c) 2026 Simon Fraser University
 * Copyright (c) 2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @brief Link to download JATS XML for an article
 *
 * @uses $jatsDownloadUrl string The URL to download the JATS XML
 *}
<a class="obj_galley_link xml" href="{$jatsDownloadUrl|escape}">
	{translate key="publication.jats.download"}
</a>
