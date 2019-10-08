# social-handle-scraper
Automatically grab social media handles from website.
Attempts to avoid other social media buttons etc on site (.e.g. sharing buttons, YouTube links, website builders)

Will use wget first to grab page content and fallback to using headless chromium browser.

## Installation
Requires `wget` and `chromium` as an executable to scrape pages, on Ubuntu:

```
sudo apt install wget chromium-browser
```
Install with composer
```
composer require cbanbury/social-handle-scraper
```

## Example
```
use CBanbury\SocialHandleScraper\HandleScraper;

$url = 'example.com';
$chrome_exec = 'chromium';
$scraper = new HandleScraper($url, $chrome_exec);
$handles = $scraper->getHandles()

--> 
[
    'facebook' => 'example',
    'twitter' => 'twitexample',
    'instagram' => 'instahandle',
    'title' => 'Page Title'
 ]
```
