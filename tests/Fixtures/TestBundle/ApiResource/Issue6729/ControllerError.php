<?php

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6729;

use ApiPlatform\Metadata\Post;
use ApiPlatform\Tests\Fixtures\TestBundle\Controller\Common\CustomController;

#[Post(controller: CustomController::class, uriTemplate: 'controller_error', openapi: false)]
class ControllerError
{
}
