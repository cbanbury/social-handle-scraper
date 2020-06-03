<?php

namespace CBanbury\SocialHandleScraper;

class SocialHandleValidator {
    private $valid;
    private $input;
    private $parsed;
    private $blacklist;

    public function __construct($handle) {
        $this->valid = false;
        $this->input = $handle;
        $this->parsed = null;

        $this->blacklist = include('Blacklist.php');
    }

    public function validate($channel) {
        if ($channel !== 'facebook' && $channel !== 'instagram' && $channel !== 'twitter') {
            return null;
        }
        $candidate_handle = $this->input;
        
        // check $channel not in query params
        $params = explode('?', $candidate_handle);
        if (count($params) > 1) {
            $temp = $params[0];
            // var_dump(strpos($channel, $temp));
            if (strpos($temp, $channel) === false) {
                return null;
            }
        }

        // remove m.facebook.com, www.facebook.com etc prefix
        $parts = explode('.com/', $candidate_handle);

        if (count($parts) > 1) {
            $candidate_handle = $parts[1];
        }

        // remove trailing slashes
        $parts = explode('/', $candidate_handle);
        if ($parts[0] === '#!' && isset($parts[1]) && strlen($parts[1]) > 0) {
            $candidate_handle = $parts[1];
        }
        $candidate_handle = $parts[0];

        // remove #'s as prefix or tags
        $candidate_handle = trim($candidate_handle, '#');
        $candidate_handle = explode('#', $candidate_handle);
        $candidate_handle = $candidate_handle[0];

        // facebook specific
        if ($channel === 'facebook') {
            // convert /pages/{handle}/{id} to {id}
            if ($candidate_handle === 'pages') {
                $parts = explode('/', $this->input);
                $candidate_handle = $parts[count($parts)-1];
            }
            
            // pg format -> should never reach this line, fallback measure
            if ($candidate_handle === 'pg') {
                return null;
            }

            // profile.php?id={id} format
            $profileId = explode('profile.php?id=', $candidate_handle);
            if (count($profileId) === 2) {
                $candidate_handle = $profileId[1];
            }

            // reject groups
            if ($candidate_handle === 'groups') {
                return null;
            }

            $fb_blacklist = array('events', 'www.facebook');
            if (in_array($candidate_handle, $fb_blacklist)) {
                return null;
            }
        }

        // instagram specfic
        if ($channel === 'instagram') {
            $insta_blacklist = array('p', 'user', 'explore');
            if (in_array($candidate_handle, $insta_blacklist)) {
                return null;
            }
            
            // instagram images
            if (strpos($this->input, 'cdninstagram') !== false) {
                return null;
            }
        }

        // remove query parameters
        $params = explode('?', $candidate_handle);
        if (count($params) > 1) {
            $candidate_handle = $params[0];
        }

        // reject *.php
        if (preg_match('/\.php/', $candidate_handle) > 0) {
            return null;
        }

        // remove @ prefix
        $candidate_handle = preg_replace('/^@/', '', $candidate_handle);
        
        // twitter specifc
        if ($channel === 'twitter') {
            $twit_blacklist = array('home', 'i', 'twitterapi', 'statuses', 'login');
            if (in_array($candidate_handle, $twit_blacklist)) {
                return null;
            }

            if ($candidate_handle === 'intent') {
                try {
                    $query = parse_url($this->input, PHP_URL_QUERY);
                    parse_str($query, $params);
                } catch (\Exception $e) {
                    return null;
                }
                
                if (!isset($params['screen_name'])) {
                    return null;
                }
                $candidate_handle = $params['screen_name'];
            }

            if (!preg_match('/^[A-Za-z0-9_]{1,15}$/', trim($candidate_handle))) {
                return null;
            }
        }

        // remove @ prefix
        $candidate_handle = preg_replace('/^@/', '', $candidate_handle);

        $final = strtolower($candidate_handle);
        if (in_array($final, $this->blacklist)) {
            return null;
        }

        $this->parsed = strtolower($candidate_handle);

        $this->valid = true;
        return $this->parsed;
    }

    // return parsed handle from validate function
    public function parsed() {
        return $this->parsed;
    }

    public function original() {
        return $this->input;
    }

    // return boolean as to whether handle is valid
    public function valid() {
        return $this->valid;
    }
}
