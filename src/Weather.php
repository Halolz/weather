<?php

/*
 * This file is part of the haloz/weather.
 *
 * (c) haloz <i@809219376@qq.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Haloz\Weather;

/*
 * This file is part of the haloz/weather.
 *
 * (c) haloz <809219376@qq.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

use GuzzleHttp\Client;
use Haloz\Weather\Exceptions\HttpException;
use Haloz\Weather\Exceptions\InvalidArgumentException;

class Weather
{
    protected $key;

    protected $guzzleOptions = [];

    public function __construct($key)
    {
        $this->key = $key;
    }

    public function getHttpClient()
    {
        return new Client($this->guzzleOptions);
    }

    public function setGuzzleOptions(array $options)
    {
        $this->guzzleOptions = $options;
    }

    public function getWeather($city, $type = 'live', $format = 'json')
    {
        $url = 'https://restapi.amap.com/v3/weather/weatherInfo';
        $types = [
            'live' => 'base',
            'forecast' => 'all',
        ];

        if (!\in_array(\strtolower($format), ['xml', 'json'])) {
            throw new InvalidArgumentException('Invalid response format: '.$format);
        }

        if (!\array_key_exists(\strtolower($type), $types)) {
            throw new InvalidArgumentException('Invalid type value(live/forecast): '.$type);
        }

        $query = array_filter([
            'key' => $this->key,
            'city' => $city,
            'output' => \strtolower($format),
            'extensions' => \strtolower($types[$type]),
        ]);

        try {
            $response = $this->getHttpClient()->get($url, [
                'query' => $query,
            ])->getBody()->getContents();

            return 'json' === $format ? \json_decode($response, true) : $response;
        } catch (\Exception $e) {
            throw new HttpException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function getLiveWeather($city, $format = 'json')
    {
        return $this->getWeather($city, 'live', $format);
    }

    public function getForecastWeather($city, $format = 'json')
    {
        return $this->getWeather($city, 'forecast', $format);
    }
}
