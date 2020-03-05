<?php


namespace CBanbury\SocialHandleScraper;

class HandleScraper {
    private $valid;
    private $supported = ['facebook', 'twitter', 'instagram'];
    private $data = [
        'title' => null
    ];
    private $candidates = [];

    public function __construct($url, $emails=false) {
        foreach($this->supported as $channel) {
            $this->data[$channel] = null;
            $this->candidates[$channel] = [];
        }

        $this->fetchEmails = false;
        if ($emails) {
            $this->fetchEmails = true;
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

        if (isset($path[0]) && isset($path[0]->textContent)) {
            $title = $path[0]->textContent;
            $this->data['title'] = $title;
        }

        if ($this->fetchEmails) {
            $emails = [];
            array_push($emails, $this->grabEmail($xpath));

            foreach(array('contact', 'about', 'terms', 'privacy') as $check) {
                $pages = $this->getPages($xpath, $check, $target_url);
                foreach($pages as $page) {
                    array_push($emails, $this->grabEmailFallback($page));
                }
            }

            $this->data['emails'] = array_values(array_filter(array_unique($emails)));
        }
    }

    private function grabEmailFallback($page) {
        if (!$page) {
            return null;
        }

        $email = $this->grabEmail($page);

        if ($email) {
            return $email;
        }

        // as a last resort, try to find email address in the text
        $email = $page->query("//p[contains(text(), '@')]");

        if (count($email) > 0) {
            $text = $email[0]->textContent;
            $parts = explode('@', $text);
            if (count($parts) === 2) {
                $name = explode(' ', $parts[0]);
                $domain = explode(' ', $parts[1]);
                $email = trim(end($name) . '@' . $domain[0]);
                $email = trim($email, '.');
                if (strlen($email) < 2) {
                    return null;
                }

                $email = $this->removeAlias($email);
                return str_replace('mailto:', '', $email);
            }
            
        }

        return null;
    }

    private function grabEmail($xpath) {
        $email = $xpath->query("//a[contains(@href, 'mailto')]");#

        if (count($email) > 0) {
            $email = $this->removeAlias($email[0]->getAttribute('href'));
            return str_replace('mailto:', '', $email);
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

    private function getPages($xpath, $search, $home) {
        $pages = $xpath->query("//a[contains(translate(text(), 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), '$search')]");
        
        $out = [];
        foreach($pages as $page) {
            $link = $page->getAttribute('href');

            if (substr($link, 0, 4 ) !== "http") {
                $link = $home . '/' . trim($link, '/');
            }

            array_push($out, $this->getContent($link));
        }

        return $out;
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

    private function removeAlias($email) {
        $parts = explode('+', $email);

        if (count($parts) !== 2) {
            return $email;
        }

        $prefix = $parts[0];

        // +foobar@gmail.com
        $domain = explode('@', $parts[1]);
        if (count($domain) === 2) {
            return $prefix . '@' . $domain[1];
        }

        return $email;
    }
}