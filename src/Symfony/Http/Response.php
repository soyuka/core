<?php

namespace ApiPlatform\Symfony\Http;

use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;
use Symfony\Component\Marshaller\MarshallerInterface;
use Symfony\Component\Marshaller\Output\OutputInterface;
use Symfony\Component\Marshaller\Output\OutputStreamOutput;

class Response extends HttpFoundationResponse
{
    public function __construct(private readonly MarshallerInterface $marshaller, private mixed $data, int $status = 200, array $headers = []) {
        parent::__construct(null, $status, $headers);
    }

    /**
     * Sends content for the current web response.
     *
     * @return $this
     */
    public function sendContent(): static
    {
        $this->marshaller->marshal($this->data, 'json', new OutputStreamOutput());

        return $this;
    }
}

