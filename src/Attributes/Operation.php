<?php

namespace ApiPlatform\Core\Attributes;

/**
 * @internal
 */
class Operation {
    public $method = 'GET';
    public array $defaults = [];
    public array $requirements = [];
    public array $options = [];
    public string $host = '';
    public array $schemes = [];
    public string $condition = '';
    public string $controller = 'api_platform.action.placeholder';
    use AttributeTrait;
}
