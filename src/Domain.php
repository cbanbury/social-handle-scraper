<?php

namespace CBanbury\SocialHandleScraper;
use LayerShifter\TLDExtract\Extract as Extract;

class Domain {
    public static function get($url) {
        $extract = new Extract();
        $domain = $extract->parse($url);

        // keep subdomain
        $domain = $domain->getFullHost();

        // strip out www.
        return preg_replace('/^www\./', '', $domain);
    }

    public static function isBlacklisted($domain) {
        $blacklist = include('DomainBlacklist.php');
        foreach($blacklist as $item) {
            // special case for sites.google subdomain, but want to block google.*
            if (strpos($domain, 'sites.google') !== false) {
                return false;
            }

            if (strpos($domain, $item) !== false) {
                return true;
            }
        }

        return false;
    }
}