<?php


namespace CBanbury\SocialHandleScraper;
use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Exception\OperationTimedOut;

class HandleScraper {
    private $valid;
    private $chrome_exec;
    private $supported = ['facebook', 'twitter', 'instagram'];
    private $data = [
        'title' => null
    ];
    private $candidates = [];

    public function __construct($url, $chrome_exec='chromium') {
        foreach($this->supported as $channel) {
            $this->data[$channel] = null;
            $this->candidates[$channel] = [];
        }

        $this->chrome_exec = $chrome_exec;
        $this->valid = true;
        $this->parse($url);
    }

    public function validDomain() {
        return $this->valid;
    }

    public function getHandles() {
        return $this->data;
    }

    public function parse($url) {
        $this->fetch($url);
        foreach($this->supported as $channel) {
            $handle = $this->grabHandle($channel);
            $this->data[$channel] = $handle;
        }
    }

    public function fetch($target_url) {
        shell_exec("wget -O - -q --timeout=20 --tries=1 $target_url > /tmp/scrape.html");
        $pageContent = file_get_contents('/tmp/scrape.html');

        if (strlen($pageContent) < 1 or strpos($pageContent, 'FailedURI') !== false) {
            // retry with www.
            shell_exec("wget -O - -q --timeout=20 --tries=1 www.$target_url > /tmp/scrape.html");
            $pageContent = file_get_contents('/tmp/scrape.html');
        }

        $type = shell_exec("file /tmp/scrape.html");

        if (strpos($type, 'HTML') === false) {
            $pageContent= shell_exec('zcat /tmp/scrape.html');
        }

        if (strlen($pageContent) < 1 or strpos($pageContent, 'FailedURI') !== false) {
            $this->clip($target_url);
        }

        $dom = new \DOMDocument();
        @$dom->loadHTML($pageContent);
        $xpath = new \DOMXPath($dom);

        foreach($this->supported as $channel) {
            $paths = $xpath->query("//a[contains(@href, '$channel.com')]");
            $links = [];
            foreach($paths as $path) {
                array_push($links, $path->getAttribute('href'));
            }

            $this->candidates[$channel] = $links;
        }

        $path = $xpath->query('//title');
        if (isset($path[0]) && isset($path[0]->textContent)) {
            $title = $path[0]->textContent;
            $this->data['title'] = $title;
        }
    }

    public function clip($target_url) {
        $browserFactory = new BrowserFactory($this->chrome_exec);
        $browser = $browserFactory->createBrowser(['noSandbox' => true, 
        'userAgent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/77.0.3865.90 Safari/537.36']);
        $page = $browser->createPage();
        $scheme = parse_url($target_url, PHP_URL_SCHEME);
        if (empty($scheme)) $target_url = "http://$target_url";
        try {
            $page->navigate($target_url)->waitForNavigation();
            $href = $page->evaluate('document.location.href')->getReturnValue();
            if (!$href or $href === 'chrome-error://chromewebdata/') {
                $this->valid = false;
                $browser->close();
                return;
            }
        } catch (OperationTimedOut $e) {
            $this->valid = false;
            return;
        } catch (\Exception $e) {
            $this->valid = false;
            return;
        }

        foreach($this->supported as $channel) {
            $this->candidates[$channel] = $page->evaluate($this->jsClosure($channel))->getReturnValue();
        }

        $this->data['title'] = $page->evaluate('document.title')->getReturnValue();
        $browser->close();
    }

    private function jsClosure($search) {
        return "(function (){a = []; document.querySelectorAll('a[href*=\"$search.com\"]').forEach((item)=>{a.push(item.href)}); return a;}())";
    }

    private function grabHandle($channel) {
        $output = null;
        foreach($this->candidates[$channel] as $link) {
            $validator = new SocialHandleValidator($link);
            $result = $validator->validate($channel);

            if ($validator->valid()) {
                $output = $result;
                break;
            }
        }

        return $output;
    }
}