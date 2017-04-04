{**
 * block.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Common site sidebar menu -- "QR" block.
 *
 * $Id$
 *}
<div class="block" id="qrBlock">
	<img class="qr" align="left" src="http://chart.apis.google.com/chart?cht=qr&chl={url page="article" op="view" 
	path=$article->getBestArticleId($currentJournal)}&chs=120x120" /><br clear="all" />
</div>	
