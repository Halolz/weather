<?php

/*
 * This file is part of the haloz/weather.
 *
 * (c) haloz <809219376@qq.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace Haloz\Weather\Tests;

use GuzzleHttp\Client;
use Haloz\Weather\Weather;
use Mockery\Matcher\AnyArgs;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\ClientInterface;
use PHPUnit\Framework\TestCase;
use Haloz\Weather\Exceptions\HttpException;
use Haloz\Weather\Exceptions\InvalidArgumentException;

class WeatherTest extends TestCase
{
    public function testGetWeather()
    {
        $response = new Response(200, [], '{"success": true}');

        $client = \Mockery::mock(Client::class);

        $client->allows()->get('https://restapi.amap.com/v3/weather/weatherInfo', [
            'query' => [
                'key'        => 'mock-key',
                'city'       => '深圳',
                'output'     => 'json',
                'extensions' => 'base',
            ],
        ])->andReturn($response);

        $w = \Mockery::mock(Weather::class, ['mock-key'])->makePartial();
        $w->allows()->getHttpClient()->andReturn($client);

        $this->assertSame(['success' => true], $w->getWeather('深圳'));

        // xml
        $response = new Response(200, [], '<hello>content</hello>');
        $client = \Mockery::mock(Client::class);
        $client->allows()->get('https://restapi.amap.com/v3/weather/weatherInfo', [
            'query' => [
                'key'        => 'mock-key',
                'city'       => '深圳',
                'output'     => 'xml',
                'extensions' => 'all',
            ],
        ])->andReturn($response);

        $w = \Mockery::mock(Weather::class, ['mock-key'])->makePartial();
        $w->allows()->getHttpClient()->andReturn($client);

        $this->assertSame('<hello>content</hello>', $w->getWeather('深圳', 'forecast', 'xml'));
    }

    public function testGetWeatherWithGuzzleRuntimeException()
    {
        $client = \Mockery::mock(Client::class);
        $client->allows()
            ->get(new AnyArgs())
            ->andThrow(new \Exception('request timeout'));

        $w = \Mockery::mock(Weather::class, ['mock-key'])->makePartial();
        $w->allows()->getHttpClient()->andReturn($client);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('request timeout');

        $w->getWeather('深圳');
    }

    public function testGetHttpClient()
    {
        $w = new Weather('mock-key');

        $this->assertInstanceOf(ClientInterface::class, $w->getHttpClient());
    }

    public function testSetGuzzleOptions()
    {
        $w = new Weather('mock-key');

        $this->assertNull($w->getHttpClient()->getConfig('timeout'));

        $w->setGuzzleOptions(['timeout' => 5000]);

        $this->assertSame(5000, $w->getHttpClient()->getConfig('timeout'));
    }

    public function testGetWeatherWithInvalidType()
    {
        $w = new Weather('mock-key');

        // 断言会抛出此异常类
        $this->expectException(InvalidArgumentException::class);

        // 断言异常消息为 'Invalid type value(base/all): foo'
        $this->expectExceptionMessage('Invalid type value(live/forecast): foo');

        $w->getWeather('深圳', 'foo');

        $this->fail('Failed to assert getWeather throw exception with invalid argument.');
    }

    public function testGetWeatherWithInvalidFormat()
    {
        $w = new Weather('mock-key');

        // 断言会抛出此异常类
        $this->expectException(InvalidArgumentException::class);

        // 断言异常消息为 'Invalid response format: array'
        $this->expectExceptionMessage('Invalid response format: array');

        // 因为支持的格式为 xml/json，所以传入 array 会抛出异常
        $w->getWeather('深圳', 'base', 'array');

        // 如果没有抛出异常，就会运行到这行，标记当前测试没成功
        $this->fail('Failed to assert getWeather throw exception with invalid argument.');
    }

    public function testGetLiveWeather()
    {
        $w = \Mockery::mock(Weather::class, ['mock-key'])->makePartial();
        $w->expects()->getWeather('深圳', 'live', 'json')->andReturn(['success' => true]);

        $this->assertSame(['success' => true], $w->getLiveWeather('深圳'));
    }

    public function testGetForecastWeather()
    {
        $w = \Mockery::mock(Weather::class, ['mock-key'])->makePartial();
        $w->expects()->getWeather('深圳', 'forecast', 'json')->andReturn(['success' => true]);

        $this->assertSame(['success' => true], $w->getForecastWeather('深圳'));
    }
}
