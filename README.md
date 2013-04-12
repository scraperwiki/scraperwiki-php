ScraperWiki PHP libraries
-------------------------

This directory contains miscellaneous PHP libraries which ScraperWiki Classic
used, and which aren't packaged in PECL or PEAR. It's checked out and added to
the `include_path` on new ScraperWiki.

You can just use it like this:

```php
require 'simple_html_dom.php'
require 'excel_reader2.php'
```

The symlinks, for example to `simple_html_dom.php`, in scraperwiki/ are for
backwards compatibility with ScraperWiki Classic scrapers. 
