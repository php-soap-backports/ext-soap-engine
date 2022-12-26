<?php

declare(strict_types=1);

namespace Soap\ExtSoapEngine;

use Soap\Engine\HttpBinding\SoapRequest;
use Soap\Engine\HttpBinding\SoapResponse;
use Soap\ExtSoapEngine\ErrorHandling\ExtSoapErrorHandler;
use Soap\ExtSoapEngine\Exception\RequestException;
use SoapClient;

final class AbusedClient extends SoapClient
{
    /**
     * @var SoapRequest|null
     */
    private $storedRequest = null;

    /**
     * @var SoapResponse|null
     */
    private $storedResponse = null;

    // @codingStandardsIgnoreStart
    /**
     * Internal SoapClient property for storing last request.
     *
     * @var string
     */
    protected $__last_request = '';
    // @codingStandardsIgnoreEnd

    // @codingStandardsIgnoreStart
    /**
     * Internal SoapClient property for storing last response.
     *
     * @var string
     */
    protected $__last_response = '';

    // @codingStandardsIgnoreEnd

    public function __construct(?string $wsdl, array $options = [])
    {
        $options = ExtSoapOptionsResolverFactory::createForWsdl($wsdl)->resolve($options);
        parent::__construct($wsdl, $options);
    }

    public static function createFromOptions(ExtSoapOptions $options): self
    {
        return new self($options->getWsdl(), $options->getOptions());
    }

    /**
     * @param string $request
     * @param string $location
     * @param string $action
     * @param int $version
     * @param int|bool $one_way
     * @return string
     */
    public function __doRequest(
        $request,
        $location,
        $action,
        $version,
        $one_way = 0
    ): string {
        $this->storedRequest = new SoapRequest($request, $location, $action, $version, (bool)$one_way);

        return $this->storedResponse ? $this->storedResponse->getPayload() : '';
    }

    public function doActualRequest(
        string $request,
        string $location,
        string $action,
        int $version,
        bool $oneWay = false
    ): string {
        $this->__last_request = $request;

        if (\PHP_VERSION_ID < 80000) {
            $oneWay = (int)$oneWay;
        }

        $this->__last_response = (string)ExtSoapErrorHandler::handleNullResponse(
            ExtSoapErrorHandler::handleInternalErrors(
                function () use ($request, $location, $action, $version, $oneWay): ?string {
                    /** @psalm-suppress InvalidScalarArgument */
                    return parent::__doRequest($request, $location, $action, $version, $oneWay);
                }
            )
        );

        return $this->__last_response;
    }

    public function collectRequest(): SoapRequest
    {
        if (!$this->storedRequest) {
            throw RequestException::noRequestWasMadeYet();
        }

        return $this->storedRequest;
    }

    public function registerResponse(SoapResponse $response): void
    {
        $this->storedResponse = $response;
    }

    public function cleanUpTemporaryState(): void
    {
        $this->storedRequest = null;
        $this->storedResponse = null;
    }

    public function __getLastRequest(): string
    {
        return $this->__last_request;
    }

    public function __getLastResponse(): string
    {
        return $this->__last_response;
    }
}
