<?php


namespace CBanbury\SocialHandleScraper;

class HandleScraper {
    private $valid;
    private $xpath;
    private $supported = ['facebook', 'twitter', 'instagram'];
    private $data = [
        'facebook' => null,
        'twitter' => null,
        'instagram' => null,
        'title' => null
    ];

    public function __construct() {
        $this->valid = true;
    }

    public function validDomain() {
        return $this->valid;
    }

    public function getHandles() {
        return $this->data;
    }

    public function parse($url) {
        $this->xpath = $this->clip($url);
        foreach($this->supported as $channel) {
            $handle = $this->grabHandle($channel);
            $this->data[$channel] = $handle;
        }
        $this->data['title'] = $this->grabTitle();
    }

    public function clip($target_url) {
        $userAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko)'.
            ' Chrome/53.0.2785.116 Safari/537.36';

        // make the cURL request to $target_url
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
        curl_setopt($ch, CURLOPT_URL,$target_url);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $html= curl_exec($ch);

        if (!$html) {
            $this->valid = false;
            return null;
        }

        // parse the html into a DOMDocument
        $dom = new \DOMDocument();
        @$dom->loadHTML($html);

        return $xpath = new \DOMXPath($dom);
    }

    public function grabTitle() {
        $path = $this->xpath->query('//meta[@property="og:title"]/@content');

        // prefer og:title
        if (isset($path[0]) && isset($path[0]->value)) {
            return $path[0]->value;
        }

        // fallback to <title> property
        $path = $this->xpath->query('//title');

        if (isset($path[0]) && isset($path[0]->textContent)) {
            $title = $path[0]->textContent;
            return $title;
        }
        return null;
    }

    private function grabHandle($channel) {
        $links = $this->xpath->query("//a[contains(@href, '$channel.com')]");
        $handle = $this->walkLinks($links, 0, "$channel.com");
        return $handle;
    }

    private function walkLinks($links, $index, $channel) {
        if (isset($links[$index]) && strlen($links[$index]->getAttribute('href')) > 0 ) {
            $candidate_path = $links[$index]->getAttribute('href');
            $validator = new SocialHandleValidator($candidate_path);
            $result = $validator->validate($channel);

            // recursive lookup
            if (!$validator->valid()) {
                if ($index === (count($links) -1)) {
                    return null;
                }
                return $this->walkLinks($links, $index + 1, $channel);
            }

            return $result;
        }
    }
}