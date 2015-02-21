{**
 * plugins/generic/googleAnalytics/pageTagUrchin.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Google Analytics urchin.js (legacy) page tag.
 *
 *}
<!-- Google Analytics -->
<script src="http://www.google-analytics.com/urchin.js" type="text/javascript">
</script>
<script type="text/javascript">
_uacct = "{$googleAnalyticsSiteId|escape}";
urchinTracker();
{foreach from=$gsAuthorAccounts item=gsAuthorAccount}
	_uff = 0; // Reset flag to allow for additional accounts
	_uacct = "{$gsAuthorAccount|escape}";
{/foreach}
</script>
<!-- /Google Analytics -->

