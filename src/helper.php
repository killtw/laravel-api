<?php

if (! function_exists('api')) {
    /**
     * @param $uri
     * @param string $method
     * @param array $parameters
     *
     * @return mixed
     */
    function api($uri, $method = 'GET', $parameters = [])
    {
        return app('api')->call($uri, $method, $parameters);
    }
}
