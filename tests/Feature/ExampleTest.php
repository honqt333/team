<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    // The default TestCase does not migrate the schema, so the welcome
    // route's call into the settings table throws a QueryException on
    // a clean test database. RefreshDatabase is the lightest possible
    // fix: it migrates once per test and rolls back afterwards.
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
