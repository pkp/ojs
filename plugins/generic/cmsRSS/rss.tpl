{**
 * rss.tpl
 *
 * Copyright (c) 2006 Gunther Eysenbach, Juan Pablo Alperin
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * RSS content
 *
 *}
<div class="separator">&nbsp;</div>
<h4><a href="{$item->get_permalink()}">{$item->get_title()|escape}</a></h4>
<small>{translate key="plugins.generic.cmsrss.permalink"} {$item->get_date('j M Y')}</small>
{$item->get_description()|truncate:500} <a href="{$item->get_permalink()}">{translate key="plugins.generic.cmsrss.permalink"}</a>
<br />
<br />
<small>from <a href="{$feed->get_feed_link()}">{$feed->get_feed_title()|escape}</a></small>
