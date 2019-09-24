<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use CBanbury\SocialHandleScraper\HandleScraper;

final class HandleGrabberTest extends TestCase
{
    public function test_tesco()
    {
        $scraper = new HandleScraper();
        $scraper->parse('https://www.tesco.com/');
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
}