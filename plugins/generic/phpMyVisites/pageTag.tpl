{**
 * pageTag.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * phpMyVisites page tag.
 *
 * $Id$
 *}
<!-- phpMyVisites -->
<a href="http://www.phpmyvisites.net/" title="phpMyVisites"
onclick="window.open(this.href);return(false);"><script type="text/javascript">
<!--
var a_vars = Array();
var pagename='';

var phpmyvisitesSite = {$phpmvSiteId};
var phpmyvisitesURL = "{$phpmvUrl}/phpmyvisites.php";
//-->
</script>
<script language="javascript" src="{$phpmvUrl}/phpmyvisites.js" type="text/javascript"></script>
<noscript><p>phpMyVisites
<img src="{$phpmvUrl}/phpmyvisites.php" alt="Statistics" style="border:0" />
</p></noscript></a>
<!-- /phpMyVisites -->

