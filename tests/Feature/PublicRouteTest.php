<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicRouteTest extends TestCase
{
    public function test_home_page_renders_without_legacy_php_router(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('A quieter way');
    }
}
