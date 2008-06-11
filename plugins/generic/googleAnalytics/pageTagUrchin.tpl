{**
 * pageTagUrchin.tpl
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Google Analytics urchin.js (legacy) page tag.
 *
 * $Id$
 *}
<!-- Google Analytics -->
<script src="http://www.google-analytics.com/urchin.js" type="text/javascript">
</script>
<script type="text/javascript">
_uacct = "{$googleAnalyticsSiteId|escape}";
urchinTracker();
</script>
<!-- /Google Analytics -->

