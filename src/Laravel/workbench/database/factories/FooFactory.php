<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Workbench\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Workbench\App\Models\Foo;

class FooFactory extends Factory
{
    protected $model = Foo::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
        ];
    }
}
