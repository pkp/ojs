{**
 * plugins/generic/openAIRE/projectIDEdit.tpl
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Edit OpenAIRE projectID 
 *
 *}
{fbvFormArea id="openAIRE"}
	{fbvFormSection label="plugins.generic.openAIRE.projectID" for="source" description="plugins.generic.openAIRE.projectID.description"}
		{fbvElement type="text" name="projectID" id="projectID" value=$projectID maxlength="255" readonly=$readOnly}
	{/fbvFormSection}
{/fbvFormArea}
