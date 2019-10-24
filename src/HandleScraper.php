<?php


namespace CBanbury\SocialHandleScraper;
use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Exception\OperationTimedOut;

class HandleScraper {
    private $valid;
    private $supported = ['facebook', 'twitter', 'instagram'];
    private $data = [
        'title' => null
    ];
    private $candidates = [];

    public function __construct($url) {
        foreach($this->supported as $channel) {
            $this->data[$channel] = null;
            $this->candidates[$channel] = [];
        }

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
            $this->valid = false;
            return;
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