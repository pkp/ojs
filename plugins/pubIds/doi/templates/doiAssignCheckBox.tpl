{**
 * @file plugins/pubIds/doi/templates/doiAssignCheckBox.tpl
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Displayed only if the DOI can be assigned.
 * Assign DOI form check box included in doiSuffixEdit.tpl and doiAssign.tpl.
 *}

{capture assign=translatedObjectType}{translate key="plugins.pubIds.doi.editor.doiObjectType"|cat:$pubObjectType}{/capture}
{capture assign=assignCheckboxLabel}{translate key="plugins.pubIds.doi.editor.assignDoi" pubId=$pubId pubObjectType=$translatedObjectType}{/capture}
{fbvFormSection list=true}
	{fbvElement type="checkbox" id="assignDoi" checked="true" value="1" label=$assignCheckboxLabel translate=false disabled=$disabled}
{/fbvFormSection}
