{**
 * plugins/generic/googleAnalytics/pageTagGa.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Google Analytics ga.js page tag.
 *
 *}
<!-- Google Analytics -->
<script type="text/javascript">
var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
</script>
<script type="text/javascript">
var pageTracker = _gat._getTracker("{$googleAnalyticsSiteId|escape}");
pageTracker._initData();
pageTracker._trackPageview();
{foreach from=$gsAuthorAccounts item=gsAuthorAccount key=k}
	var authorTracker{$k|escape} = _gat._getTracker("{$gsAuthorAccount|escape}");
	authorTracker{$k|escape}._setDomainName("none");
	authorTracker{$k|escape}._setAllowLinker(true);
	authorTracker{$k|escape}._initData();
	authorTracker{$k|escape}._trackPageview();
{/foreach}
</script>
<!-- /Google Analytics -->

