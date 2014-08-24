{**
 * plugins/generic/pln/templates/setup.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * PLN plugin setup instructions for journal managers
 *
 *}
{plugin_url|assign:"plnPluginURL" page="manager" op="plugin" path="settings"}
<p>{translate key="plugins.generic.pln.manager.setup.description" plnPluginURL=$plnPluginURL}</p>