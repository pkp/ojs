{**
 * templates/locale.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * List of component locales to edit for a particular locale
 *}
{assign var=templateId value=""|uniqid}
<p>{translate key="plugins.generic.translator.localeDescription"}</p>

<h3>{translate key="plugins.generic.translator.jumpTo"}</h3>
<ul>
	<li><a href="#localeFiles-{$templateId}">{translate key="plugins.generic.translator.localeFiles"}</a>
	<li><a href="#miscFiles-{$templateId}">{translate key="plugins.generic.translator.miscFiles"}</a>
	<li><a href="#emails-{$templateId}">{translate key="plugins.generic.translator.emails"}</a>
</ul>

<a name="localeFiles-{$templateId}"></a>

{url|assign:localeFileGridUrl router=$smarty.const.ROUTE_COMPONENT component="plugins.generic.translator.controllers.grid.LocaleFileGridHandler" op="fetchGrid" locale=$locale tabsSelector=$tabsSelector escape=false}
{load_url_in_div id="localeFileGridContainer-"|concat:$templateId url=$localeFileGridUrl}

<a name="miscFiles-{$templateId}"></a>

{url|assign:miscFileGridUrl router=$smarty.const.ROUTE_COMPONENT component="plugins.generic.translator.controllers.grid.MiscTranslationFileGridHandler" op="fetchGrid" locale=$locale tabsSelector=$tabsSelector escape=false}
{load_url_in_div id="miscFileGridContainer-"|concat:$templateId url=$miscFileGridUrl}

<a name="emails-{$templateId}"></a>

{url|assign:emailGridUrl router=$smarty.const.ROUTE_COMPONENT component="plugins.generic.translator.controllers.grid.EmailGridHandler" op="fetchGrid" locale=$locale tabsSelector=$tabsSelector escape=false}
{load_url_in_div id="emailGridContainer-"|concat:$templateId url=$emailGridUrl}

</table>
