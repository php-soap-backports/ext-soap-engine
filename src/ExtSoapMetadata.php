<?php

declare(strict_types=1);

namespace Soap\ExtSoapEngine;

use Soap\Engine\Metadata\Collection\MethodCollection;
use Soap\Engine\Metadata\Collection\TypeCollection;
use Soap\Engine\Metadata\Collection\XsdTypeCollection;
use Soap\Engine\Metadata\Metadata;
use Soap\ExtSoapEngine\Metadata\MethodsParser;
use Soap\ExtSoapEngine\Metadata\TypesParser;
use Soap\ExtSoapEngine\Metadata\XsdTypesParser;

final class ExtSoapMetadata implements Metadata
{

    /**
     * @var AbusedClient
     */
    private $abusedClient;

    /**
     * @var XsdTypeCollection|null
     */
    private $xsdTypes = null;

    public function __construct(AbusedClient $abusedClient)
    {
        $this->abusedClient = $abusedClient;
    }

    public function getMethods(): MethodCollection
    {
        return (new MethodsParser($this->getXsdTypes()))->parse($this->abusedClient);
    }

    public function getTypes(): TypeCollection
    {
        return (new TypesParser($this->getXsdTypes()))->parse($this->abusedClient);
    }

    private function getXsdTypes(): XsdTypeCollection
    {
        if (null === $this->xsdTypes) {
            $this->xsdTypes = XsdTypesParser::default()->parse($this->abusedClient);
        }

        return $this->xsdTypes;
    }
}
