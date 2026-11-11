<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // The slug is UNIQUE in the database, and Faker's company names
        // come from a finite list — two factories in the same test run
        // can produce the same name, which made the second insert fail
        // with `UNIQUE constraint failed: tenants.slug`.
        //
        // The uniqueness has to be intrinsic to the FACTORY: a test
        // should not have to know that the company names might repeat.
        // A random suffix on the slug makes every tenant unique while
        // leaving the human-readable name untouched.
        $name = fake()->company();
        $slug = Str::slug($name);

        if (Tenant::withTrashed()->where('slug', $slug)->exists()) {
            $slug .= '-'.Str::lower(Str::random(6));
        }

        return [
            'name' => $name,
            'slug' => $slug,
            'status' => 'active',
        ];
    }

    /**
     * Indicate that the tenant is in readonly mode.
     */
    public function readonly(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'readonly',
        ]);
    }

    /**
     * Indicate that the tenant is suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'suspended',
        ]);
    }
}
