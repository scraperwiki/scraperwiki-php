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


## API reference:

#### scraperwiki::scrape($url)

Scrape a given `$url` and return the response content as a string.

#### scraperwiki::save_sqlite($unique_keys, $data, [$table_name])

Save data into a SQLite database (created automatically in the background).

`$unique_keys` (required) should be an `array()` of one or more column names.

`$data` (required) should either be a single row to save (as an `array()` of strings and/or numbers), or a list of multiple rows to save in one go (as an `array()` of `array()`s)

`$table_name` is optional. If unspecified, the default of `"swdata"` will be used. You can pass any string you like here to save your data into a new table name.

#### scraperwiki:save_var($name, $value)

Save a given variable `$name` with a given `$value` to the datastore.

#### scraperwiki:get_var($name, [$default])

Retrieve a given variable `$name` from the datastore.

If you pass an optional `$default` value, this will be returned in the case that the given variable `$name` could not be found in the datastore.
