# social-handle-scraper
Automatically grab social media handles from website.
Attempts to avoid other social media buttons etc on site (.e.g. sharing buttons, YouTube links, website builders)

Will use wget first to grab page content.

## Installation
Requires `wget` as an executable to scrape pages, on Ubuntu:

```
sudo apt install wget chromium-browser

## Example
```
use CBanbury\SocialHandleScraper\HandleScraper;

$url = 'example.com';
$scraper = new HandleScraper($url);
$handles = $scraper->getHandles()

--> 
[
    'facebook' => 'example',
    'twitter' => 'twitexample',
    'instagram' => 'instahandle',
    'title' => 'Page Title'
 ]
```
