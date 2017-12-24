<?php

namespace Hal\UI;

use PHPUnit\Framework\TestCase;

class SharedStaticConfigurationTest extends TestCase
{
    public function testPageSizes()
    {
        $this->assertSame(25, SharedStaticConfiguration::SMALL_PAGE_SIZE);
        $this->assertSame(50, SharedStaticConfiguration::LARGE_PAGE_SIZE);
        $this->assertSame(100, SharedStaticConfiguration::HUGE_PAGE_SIZE);
    }
}
