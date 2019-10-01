# social-handle-scraper
Automatically grab social media handles from website.
Attempts to avoid other social media buttons etc on site (.e.g. sharing buttons, YouTube links, website builders)

## Installation
Requires `google-chrome` as an executable to scrape pages, on Ubuntu:

```
sudo apt install chromium-browser
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
try {
    $scraper = new HandleScraper($url, $chrome_exec);
}

$handles = $scraper->getHandles()

--> 
[
    'facebook' => 'example',
    'twitter' => 'twitexample',
    'instagram' => 'instahandle',
    'title' => 'Page Title'
 ]
```
