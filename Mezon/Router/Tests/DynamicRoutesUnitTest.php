<?php
namespace Mezon\Router\Tests;

use Mezon\Router\Router;
use Mezon\Router\Types\DateRouterType;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
class DynamicRoutesUnitTest extends \PHPUnit\Framework\TestCase
{

    /**
     * Default setup
     *
     * {@inheritdoc}
     * @see \PHPUnit\Framework\TestCase::setUp()
     */
    public function setUp(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    const TYPES_ROUTE_CATALOG_INT_BAR = '/catalog/[i:bar]/';

    const TYPES_ROUTE_CATALOG_FIX_POINT_BAR = '/catalog/[fp:bar]/';

    /**
     * Data provider for the testTypes
     *
     * @return array test data
     */
    public function typesDataProvider(): array
    {
        $data = [
            // #0
            [
                DynamicRoutesUnitTest::TYPES_ROUTE_CATALOG_INT_BAR,
                '/catalog/1/',
                1
            ],
            // #1
            [
                DynamicRoutesUnitTest::TYPES_ROUTE_CATALOG_INT_BAR,
                '/catalog/-1/',
                - 1
            ],
            // #2
            [
                DynamicRoutesUnitTest::TYPES_ROUTE_CATALOG_INT_BAR,
                '/catalog/+1/',
                1
            ],
            // #3
            [
                DynamicRoutesUnitTest::TYPES_ROUTE_CATALOG_FIX_POINT_BAR,
                '/catalog/1.1/',
                1.1
            ],
            // #4
            [
                DynamicRoutesUnitTest::TYPES_ROUTE_CATALOG_FIX_POINT_BAR,
                '/catalog/-1.1/',
                - 1.1
            ],
            // #5
            [
                DynamicRoutesUnitTest::TYPES_ROUTE_CATALOG_FIX_POINT_BAR,
                '/catalog/+1.1/',
                1.1
            ],
            // #6
            [
                '/[a:bar]/',
                '/.-@/',
                '.-@'
            ],
            // #7
            [
                '/[s:bar]/',
                '/, ;:/',
                ', ;:'
            ],
            // #8
            [
                [
                    '/[fp:number]/',
                    '/[s:bar]/'
                ],
                '/abc/',
                'abc'
            ],
            // #9
            [
                '/catalog/[il:bar]/',
                '/catalog/123,456,789/',
                '123,456,789'
            ],
            // #10
            [
                '/catalog/[s:bar]/',
                '/catalog/123&456/',
                '123&456'
            ],
            // #11, parameter name chars testing
            [
                '/[s:Aa_x-0]/',
                '/abc123/',
                'abc123',
                'Aa_x-0'
            ],
            // #12, date type testing 1
            [
                '/[date:dfield]/',
                '/2020-02-02/',
                '2020-02-02',
                'dfield'
            ],
            // #13, date type testing 2
            [
                '/posts-[date:dfield]/',
                '/posts-2020-02-02/',
                '2020-02-02',
                'dfield'
            ]
        ];

        $return = [];

        foreach (Router::getListOfSupportedRequestMethods() as $method) {
            $tmp = array_merge($data);

            foreach ($tmp as $item) {
                $item = array_merge([
                    $method
                ], $item);
                $return[] = $item;
            }
        }

        return $return;
    }

    /**
     * Testing router types
     *
     * @param mixed $pattern
     *            route pattern
     * @param string $route
     *            real route
     * @param mixed $expected
     *            expected value
     * @param string $paramName
     *            name of the validating parameter
     * @dataProvider typesDataProvider
     */
    public function testTypes(string $method, $pattern, string $route, $expected, string $paramName = 'bar'): void
    {
        // setup
        $_SERVER['REQUEST_METHOD'] = $method;
        $router = new \Mezon\Router\Router();
        $router->addType('date', DateRouterType::class);
        if (is_string($pattern)) {
            $router->addRoute($pattern, function () {
                // do nothing
            }, $method);
        } else {
            foreach ($pattern as $r) {
                $router->addRoute($r, function () {
                    // do nothing
                }, $method);
            }
        }
        $router->callRoute($route);

        // test body and assertions
        $this->assertEquals($expected, $router->getParam($paramName));
    }

    /**
     * Testing multyple routes
     */
    public function testMultyple(): void
    {
        // setup
        $router = new Router();
        for ($i = 0; $i < 15; $i ++) {
            $router->addRoute('/multiple/' . $i . '/[i:id]', function () {
                return 'done!';
            });
        }

        // test body
        $result = $router->callRoute('/multiple/' . rand(0, 14) . '/12345');

        // assertions
        $this->assertEquals('done!', $result);
        $this->assertEquals('12345', $router->getParam('id'));
    }

    /**
     * Testing real life example #1
     */
    public function testRealLifeExample1(): void
    {
        // setup
        $router = new Router();
        $router->addRoute('/user/[s:login]/custom-field/[s:name]', function () {
            return 'get-custom-field';
        });
        $router->addRoute('/user/[s:login]/custom-field/[s:name]/add', function () {
            return 'add-custom-field';
        });
        $router->addRoute('/user/[s:login]/custom-field/[s:name]/delete', function () {
            return 'delete-custom-field';
        });
        $router->addRoute('/restore-password/[s:token]', function () {
            return 'restore-password';
        });
        $router->addRoute('/reset-password/[s:token]', function () {
            return 'reset-password';
        });
        $router->addRoute('/user/[s:login]/delete', function () {
            return 'user-delete';
        });

        // test body
        $result = $router->callRoute('/user/index@localhost/custom-field/name/add');

        // assertions
        $this->assertEquals('add-custom-field', $result);
    }
}
