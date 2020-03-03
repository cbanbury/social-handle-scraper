<?php


namespace CBanbury\SocialHandleScraper;

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
        $url = Domain::get($url);
        if (strlen($url) < 1) {
            $this->valid = false;
        }

        if (Domain::isBlacklisted($url)) {
            $this->valid = false;
        }
        
        $this->parse($url);
    }

    public function validDomain() {
        return $this->valid;
    }

    public function getHandles() {
        return $this->data;
    }

    public function parse($url) {
        if ($this->valid) {
            $this->fetch($url);
            foreach($this->supported as $channel) {
                $handle = $this->grabHandle($channel);
                $this->data[$channel] = $handle;
            }
        }
    }

    public function fetch($target_url) {
        $xpath = $this->getContent($target_url);

        if ($xpath === null) {
            $this->valid = false;
            return;
        }

        foreach($this->supported as $channel) {
            $paths = $xpath->query("//a[contains(@href, '$channel.com')]");
            $links = [];
            foreach($paths as $path) {
                array_push($links, $path->getAttribute('href'));
            }

            $this->candidates[$channel] = $links;
        }

        $path = $xpath->query('//title');
        $email = $this->grabEmail($xpath);

        if (!$email) {
            foreach(array('contact', 'about') as $check) {
                $contactPage = $this->getPage($xpath, $check);
                if ($contactPage) {
                    $email = $this->grabEmail($contactPage);
                }
                
                if ($email) {
                    break;
                }
            }
        }

        // fallback to using terms and conditions page
        $email = $this->grabEmailFallback($xpath);

        $this->data['email'] = str_replace('mailto:', '', $email);

        if (isset($path[0]) && isset($path[0]->textContent)) {
            $title = $path[0]->textContent;
            $this->data['title'] = $title;
        }
    }

    private function grabEmailFallback($xpath) {
        // find terms/conditions page
        $terms = $this->getPage($xpath, 'terms');
        if (!$terms) {
            $terms = $this->getPage($xpath, 'privacy');
        }

        if (!$terms) {
            return null;
        }

        $email = $terms->query("//p[contains(text(), '@')]");

        if (count($email) > 0) {
            $text = $email[0]->textContent;
            $parts = explode('@', $text);
            if (count($parts) === 2) {
                $name = explode(' ', $parts[0]);
                $domain = explode(' ', $parts[1]);
                $email = trim(end($name) . '@' . $domain[0]);
                var_dump($email);
                return trim($email, '.');
            }
            
        }

        return null;
    }

    private function grabEmail($xpath) {
        $email = $xpath->query("//a[contains(@href, 'mailto')]");

        if (count($email) > 0) {
            return $email[0]->getAttribute('href');
        }

        return null;
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

    private function getPage($xpath, $search) {
        $pages = $xpath->query("//a[contains(translate(text(), 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), '$search')]");

        // TODO: how to handle relative urls! :(
        // foreach($pages as $page) {
        //     var_dump($page->getAttribute('href'));
        // }
        
        if (count($pages) > 0) {
            
            return $this->getContent($pages[0]->getAttribute('href'));
        }

        return null;
    }

    private function getContent($url) {
        shell_exec("wget -O - -q --timeout=20 --tries=1 $url > /tmp/scrape.html");
        $pageContent = file_get_contents('/tmp/scrape.html');

        if (strlen($pageContent) < 1 or strpos($pageContent, 'FailedURI') !== false) {
            // retry with www.
            shell_exec("wget -O - -q --timeout=20 --tries=1 www.$url > /tmp/scrape.html");
            $pageContent = file_get_contents('/tmp/scrape.html');
        }

        $type = shell_exec("file /tmp/scrape.html");

        if (strpos($type, 'HTML') === false) {
            $pageContent= shell_exec('zcat /tmp/scrape.html 2>/dev/null');
        }

        if (strlen($pageContent) < 1 or strpos($pageContent, 'FailedURI') !== false) {
            return null;
        }

        $dom = new \DOMDocument();
        @$dom->loadHTML($pageContent);
        return new \DOMXPath($dom);
    }
}