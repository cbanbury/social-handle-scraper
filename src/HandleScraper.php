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
        $this->clip($url);
        foreach($this->supported as $channel) {
            $handle = $this->grabHandle($channel);
            $this->data[$channel] = $handle;
        }
    }

    public function clip($target_url) {
        $browserFactory = new BrowserFactory($this->chrome_exec);
        $browser = $browserFactory->createBrowser(['noSandbox' => true]);
        $page = $browser->createPage();
        $scheme = parse_url($target_url, PHP_URL_SCHEME);
        if (empty($scheme)) $target_url = "http://$target_url";

        $page->navigate($target_url)->waitForNavigation();
        $href = $page->evaluate('document.location.href')->getReturnValue();
        if (!$href or $href === 'chrome-error://chromewebdata/') {
            $this->valid = false;
            $browser->close();
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