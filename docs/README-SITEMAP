Sitemap Documentation
Matt Crider, 12/1/2008

Adding a Sitemap to your website informs search engines about the URLs on your web site that you want to be indexed.  This allows web crawlers to more intelligently index your site, and to index pages that may be difficult to find.  Sitemaps also allow you to inform the engine other information about each page, such as modification frequency, when it was last changed, and how important it is in relation to your other pages.  Please see http://www.sitemaps.org/protocol.php for more information.

To enable a Sitemap for your site, you need to inform search engines about your sitemap URL.  This is located at:
	<loc>http://YOURDOMAIN.COM/PATH/TO/OxS/index.php/index/sitemap/</loc>
You will have to change the domain your own site's domain, and also change the path to OJS/OCS to match the full path after the domain name.  Leave the rest of the URL there--it is the path to a function that will dynamically generate the XML file for web crawlers to use.  This file is referred to as a Sitemap index--A container to list all of the other Sitemaps (in this case, one for each journal).

Please see http://www.sitemaps.org/protocol.php#informing for more information on informing search engines about your Sitemap.  The quickest method is to submit the Sitemap directly to the search engines you wish to be listed under, but it is also a good idea to have a robots.txt file directing web crawlers to the Sitemap in case there are search engines you are not aware of crawling your site.

Note that this will only inform web crawlers of the automatically generated sitemap, which will not include any of the additional tags that can be added to your sitemap (i.e. lastmod, changefreq, and priority).  If you wish to customize your Sitemap, we suggest adding a new sitemap to the root directory of your OJS/OCS installation, and then copy the Sitemaps for each journal (produced when visiting http://YOURDOMAIN.COM/PATH/TO/OJS/index.php/YOURJOURNAL/sitemap/) into it.  You can then add additional pages on your site and customize the XML with the additional available tags (http://www.sitemaps.org/protocol.php#xmlTagDefinitions).  Further, you can put the Sitemap higher in the directory hierarchy, allowing you to add OJS/OCS and any other URLS that are below that directory level.

Note also that if disable_path_info is set to 'On' in your config.inc.php file, you will have to use a different URL for submitting to search engines.  The equivalent URL to index.php/index/sitemap with path info disabled is 
	index.php?journal=index&amp;page=sitemap
This URL is not valid for your web browser, but search engines require ampersands (&) to be escaped and will be encoded correctly.