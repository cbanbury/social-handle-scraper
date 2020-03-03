<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use CBanbury\SocialHandleScraper\HandleScraper;

final class HandleGrabberTest extends TestCase
{
    public function test_tesco_http()
    {
        $scraper = new HandleScraper('tesco.com');
        $handles = $scraper->getHandles();

        $this->assertEquals(
            'tesco',
            $handles['facebook']
        );

        $this->assertEquals(
            'tesco',
            $handles['twitter']
        );

        $this->assertEquals(
            'tescofood',
            $handles['instagram']
        );
    }

    public function test_waitrose_http()
    {
        $scraper = new HandleScraper('waitrose.co.uk');
        $handles = $scraper->getHandles();

        $this->assertEquals(
            'waitroseandpartners',
            $handles['facebook']
        );

        $this->assertEquals(
            'waitrose',
            $handles['twitter']
        );

        $this->assertEquals(
            'waitroseandpartners',
            $handles['instagram']
        );
    }

    public function test_boots_without_http()
    {
        $scraper = new HandleScraper('boots.com');
        $handles = $scraper->getHandles();

        $this->assertEquals(
            'bootsofficialuk',
            $handles['facebook']
        );

        $this->assertEquals(
            'bootsuk',
            $handles['twitter']
        );

        $this->assertEquals(
            'bootsuk',
            $handles['instagram']
        );
    }

    public function test_junk_url()
    {
        $scraper = new HandleScraper('abdcabafjeweedsasd');
        $handles = $scraper->getHandles();

        $this->assertEquals(
            null,
            $handles['facebook']
        );

        $this->assertEquals(
            null,
            $handles['twitter']
        );

        $this->assertEquals(
            null,
            $handles['instagram']
        );

        $this->assertEquals(false, $scraper->validDomain());
    }

    public function test_shopify_site()
    {
        $scraper = new HandleScraper('funanimalart.co.uk');
        $handles = $scraper->getHandles();

        $this->assertEquals(
            'funanimalart',
            $handles['facebook']
        );

        $this->assertEquals(
            'funanimalart',
            $handles['instagram']
        );
    }

    public function test_blacklist_facebook_domain()
    {
        $scraper = new HandleScraper('facebook.com/foo');
        $handles = $scraper->getHandles();

        $this->assertEquals($scraper->validDomain(), false);
    }
    
    public function test_intent_url()
    {
        $scraper = new HandleScraper('www.eberhardt-travel.de');
        $handles = $scraper->getHandles();
        
        $this->assertEquals(
            'richtig_reisen',
            $handles['twitter']
        );
    }
    
    public function test_amazon_redirect()
    {
        $scraper = new HandleScraper('jdlandis.com');
        $handles = $scraper->getHandles();
        
        $this->assertEquals(
            null,
            $handles['facebook']
        );
    }

    public function test_at_prefix_removed()
    {
        $scraper = new HandleScraper('number10.gov.uk');
        $handles = $scraper->getHandles();
        
        $this->assertEquals(
            '10downingstreet',
            $handles['twitter']
        );
    }

    public function test_email()
    {
        $scraper = new HandleScraper('modainpelle.com');
        $handles = $scraper->getHandles();

        $this->assertEquals(
            'customerservices@modainpelle.com',
            $handles['email']
        );
    }

    public function test_terms_email() 
    {
        $scraper = new HandleScraper('boohoo.com');
        $handles = $scraper->getHandles();

        $this->assertEquals(
            'customerservcies@boohoo.com',
            $handles['email']
        );
    }
}