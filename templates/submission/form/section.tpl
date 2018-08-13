{**
 * templates/submission/form/section.tpl
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Include section placement for submissions.
 *}
{assign var=sectionDescription value=""}
{if !$readOnly}
	{assign var=sectionDescription value="author.submit.journalSectionDescription"}
{/if}
{fbvFormSection title="section.section" required=true}
	{fbvElement type="select" id="sectionId" label=$sectionDescription from=$sectionOptions selected=$sectionId translate=false disabled=$readOnly size=$fbvStyles.size.MEDIUM required=true}
{/fbvFormSection}
