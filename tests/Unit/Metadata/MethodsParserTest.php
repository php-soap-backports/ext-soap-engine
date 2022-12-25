<?php
declare(strict_types=1);

namespace SoapTest\ExtSoapEngine\Unit\Metadata;

use PHPUnit\Framework\TestCase;
use Soap\Engine\Metadata\Collection\ParameterCollection;
use Soap\Engine\Metadata\Collection\XsdTypeCollection;
use Soap\Engine\Metadata\Model\Method;
use Soap\Engine\Metadata\Model\Parameter;
use Soap\Engine\Metadata\Model\XsdType;
use Soap\ExtSoapEngine\Metadata\MethodsParser;
use SoapClient;

final class MethodsParserTest extends TestCase
{
    private $parser;

    protected function setUp(): void
    {
        $this->parser = new MethodsParser(
            new XsdTypeCollection(
                XsdType::create('simpleType')
                    ->withBaseType('string')
            )
        );
    }

    public function test_it_can_parse_ext_soap_function_strings()
    {
        $client = $this->createConfiguredMock(SoapClient::class, [
            '__getFunctions' => $methods = [
                'TestResponse Test0Param()',
                'TestResponse Test1Param(Test1 $parameter1)',
                'TestResponse Test2Param(Test1 $parameter1, Test2 $parameter2)',
                'list(Response1 $response1, Response2 $response2) TestReturnList()',
                'list(Response1 $response1, Response2 $response2) TestReturnListWithParams(Test1 $parameter1, Test2 $parameter2)',
                'simpleType TestSimpleType(simpleType $parameter1)',
            ]
        ]);

        $result = $this->parser->parse($client);

        static::assertCount(count($methods), $result);
        static::assertEquals(
            new Method(
                'Test0Param',
                new ParameterCollection(),
                XsdType::create('TestResponse')
            ),
            $result->fetchByName('Test0Param')
        );
        static::assertEquals(
            new Method(
                'Test1Param',
                new ParameterCollection(
                    new Parameter('parameter1', XsdType::create('Test1'))
                ),
                XsdType::create('TestResponse')
            ),
            $result->fetchByName('Test1Param')
        );
        static::assertEquals(
            new Method(
                'Test2Param',
                new ParameterCollection(
                    new Parameter('parameter1', XsdType::create('Test1')),
                    new Parameter('parameter2', XsdType::create('Test2'))
                ),
                XsdType::create('TestResponse')
            ),
            $result->fetchByName('Test2Param')
        );
        static::assertEquals(
            new Method(
                'TestReturnList',
                new ParameterCollection(),
                XsdType::create('array')
            ),
            $result->fetchByName('TestReturnList')
        );
        static::assertEquals(
            new Method(
                'TestReturnListWithParams',
                new ParameterCollection(
                    new Parameter('parameter1', XsdType::create('Test1')),
                    new Parameter('parameter2', XsdType::create('Test2'))
                ),
                XsdType::create('array')
            ),
            $result->fetchByName('TestReturnListWithParams')
        );
        static::assertEquals(
            new Method(
                'TestSimpleType',
                new ParameterCollection(
                    new Parameter('parameter1', XsdType::create('simpleType')->withBaseType('string'))
                ),
                XsdType::create('simpleType')->withBaseType('string')
            ),
            $result->fetchByName('TestSimpleType')
        );
    }
}
