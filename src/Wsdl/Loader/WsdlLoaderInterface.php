<?php

namespace Soap\ExtSoapEngine\Wsdl\Loader;

interface WsdlLoaderInterface
{
    public function __invoke(string $location): string;
}