<?php

namespace Soap\ExtSoapEngine\Wsdl\Loader;

use Exception;
use RuntimeException;

class StreamWsdlLoader implements WsdlLoaderInterface
{

    private $context;

    public function __construct($context = null)
    {
        $this->context = $context;
    }

    public function __invoke(string $location): string
    {
        try {
            $content = file_get_contents(
                $location,
                false,
                is_resource($this->context) ? $this->context : null
            );
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage(), (int)$e->getCode(), $e);
        }

        if ($content === false) {
            throw new RuntimeException(sprintf('Could not load WSDL from location "%s"', $location));
        }

        return $content;
    }
}