{**
 * controllers/tab/settings/appearance/form/lists.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Form fields for setting list options
 *
 *}
{fbvFormArea id="advancedAppearanceSettings" title="manager.setup.lists"}
	{fbvFormSection description="manager.setup.listsDescription"}
		{fbvElement type="text" id="itemsPerPage" value=$itemsPerPage size=$fbvStyles.size.SMALL label="common.itemsPerPage"}
	{/fbvFormSection}
	{fbvFormSection}
		{fbvElement type="text" id="numPageLinks" value=$numPageLinks size=$fbvStyles.size.SMALL label="manager.setup.numPageLinks"}
	{/fbvFormSection}
{/fbvFormArea}
