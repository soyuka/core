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

namespace Workbench\App\Models;

use ApiPlatform\JsonApi\Filter\SparseFieldset;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\IsApiResource;
use ApiPlatform\Metadata\QueryParameter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ApiResource(
    operations: [
        new GetCollection(
            parameters: [
                new QueryParameter(key: 'fields', filter: SparseFieldset::class),
            ],
        ),
    ]
)]
class Foo extends Model
{
    use HasFactory;
    use IsApiResource;

    protected $fillable = ['name'];

    public function bars(): HasMany
    {
        return $this->hasMany(Bar::class);
    }
}
