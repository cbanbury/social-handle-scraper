# social-handle-scraper
Automatically grab social media handles from website.
Attempts to avoid other social media buttons etc on site (.e.g. sharing buttons, YouTube links, website builders)

## Installation


## Example
```
use CBanbury\SocialHandleScraper\HandleScraper;

$scraper = new HandleScraper();
$url = 'example.com'
$scraper->parse($url);
$handles = $scraper->getHandles()

--> 
[
    'facebook' => 'example',
    'twitter' => 'twitexample',
    'instagram' => 'instahandle',
    'title' => 'Page Title'
 ]
```
