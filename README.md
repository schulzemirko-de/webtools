Here you will find scripts that serve as useful tools.


## Indexnow
Indexnow is a standard that makes it possible to proactively inform a search engine about changes to the content of a website.
This "saves" the search engine operator from having to constantly retrieve and compare the website cached in the search index. Unfortunately, Google does not yet offer this service.

Simply copy indexnow.php and indexnow.settings.php into a directory of your choice and integrate them as a CronJob. An execution interval of 60 minutes should be sufficient so that the search engine operator is not spammed with change notices regarding temporary intermediate statuses. 

## Sitemap-Tool
The SiteMap tool can be used to generate a sitemap via CronJob. This can be entered in robots.txt or communicated to a search engine.
