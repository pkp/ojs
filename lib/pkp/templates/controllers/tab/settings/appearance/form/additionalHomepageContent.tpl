{**
 * controllers/tab/settings/appearance/form/additionalHomepageContent.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief TinyMCE field for adding additional content to the homepage
 *
 *}
{fbvFormSection for="additionalHomeContent" label="manager.setup.additionalContent" description="manager.setup.additionalContentDescription"}
	{fbvElement type="textarea" multilingual=true name="additionalHomeContent" id="additionalHomeContent" value=$additionalHomeContent rich=true}
{/fbvFormSection}
