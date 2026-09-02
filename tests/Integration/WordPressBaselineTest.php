<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class WordPressBaselineTest extends TestCase
{
    public function testWordPressIsLoaded(): void
    {
        $this->assertTrue(defined('ABSPATH'));
        $this->assertTrue(defined('WPINC'));
    }
}