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

        // remove @ prefix
        $candidate_handle = preg_replace('/^@/', '', $candidate_handle);

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

        // facebook specific
        if ($channel === 'facebook') {
            // convert /pages/{handle}/{id} to {id}
            if ($candidate_handle === 'pages') {
                $parts = explode('/', $this->input);
                $candidate_handle = $parts[count($parts)-1];
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
        }

        // instagram specfic
        if ($channel === 'instagram') {
            if ($candidate_handle === 'p') {
                return null;
            }

            if ($candidate_handle === 'user') {
                return null;
            }

            // remove explore to instagram
            if ($candidate_handle === 'explore') {
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