ScraperWiki PHP libraries
-------------------------

This directory contains PHP libraries which ScraperWiki Classic used, and which
aren't packaged in PECL or PEAR.

It's checked out and added to the `include_path` on new ScraperWiki.

The symlinks, for example to `simple_html_dom.php`, in scraperwiki/ are for
backwards compatibility with ScraperWiki Classic scrapers. You can just
use it like this:

   require 'simple_html_dom.php'
   require 'excel_reader2.php'


