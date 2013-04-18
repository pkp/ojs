{**
 * templates/submission/form/section.tpl
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Include section placement for submissions.
 *}
{if count($sectionOptions) > 1} {* only display the section picker if there are section configured for this journal *}
	{fbvFormSection label="section.section" description="submission.submit.placement.sectionDescription"}
		{fbvElement type="select" id="sectionId" from=$sectionOptions selected=$sectionId translate=false disabled=$readOnly}
	{/fbvFormSection}
{/if}
