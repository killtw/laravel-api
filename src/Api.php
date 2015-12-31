<?php

namespace Killtw\Api;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class Api
 *
 * @method mixed get($url, $parameters)
 * @method mixed post($url, $parameters)
 * @method mixed put($url, $parameters)
 * @method mixed patch($url, $parameters)
 * @method mixed delete($url, $parameters)
 *
 * @package Killtw\Api
 */
class Api
{
    /**
     * @var Request
     */
    private $request;
    /**
     * @var Router
     */
    private $router;
    /**
     * @var Client
     */
    private $client;

    /**
     * Api constructor.
     *
     * @param Request $request
     * @param Router $router
     * @param Client $client
     */
    public function __construct(Request $request, Router $router, Client $client)
    {
        $this->request = $request;
        $this->router = $router;
        $this->client = $client;
    }

    /**
     * @param $uri
     * @param $method
     * @param array $parameters
     * @param bool $collection
     *
     * @return mixed|string
     */
    public function call($uri, $method, $parameters = [], $collection = true)
    {
        try {
            $request = $this->request->create($uri, $method, $parameters);
            $dispatch = $this->router->dispatch($request);

            return $this->getResponse($dispatch, $dispatch->getContent(), $collection);
        } catch (NotFoundHttpException $e) {
            throw new NotFoundHttpException('Request Not Found.');
        }
    }

    /**
     * @param $uri
     * @param $method
     * @param array $parameters
     * @param bool $collection
     *
     * @return mixed|string
     */
    public function callRemote($uri, $method, $parameters= [], $collection = true)
    {
        try {
            $request = $this->client->request($method, $uri, $parameters);

            return $this->getResponse($request, $request->getBody()->getContents(), $collection);
        } catch (RequestException $e) {
            throw new NotFoundHttpException('Request Not Found.');
        }
    }

    /**
     * @param $request
     *
     * @return bool
     */
    private function isContentAJson($request)
    {
        if (method_exists($request, 'getHeader')) {
            $contentType = $request->getHeader('CONTENT-TYPE');
        } else {
            $contentType = $request->headers->get('CONTENT-TYPE');
        }

        return ($contentType == 'application/json');
    }

    /**
     * @param $request
     * @param $response
     * @param $collection
     *
     * @return \Illuminate\Support\Collection
     */
    private function getResponse($request, $response, $collection)
    {
        if ($this->isContentAJson($request)) {
            $response = json_decode($response);

            if ($collection) {
                $response = $this->transformContentToCollection($response);
            }
        }

        return $response;
    }

    /**
     * @param $response
     *
     * @return \Illuminate\Support\Collection
     */
    private function transformContentToCollection($response)
    {
        $result = new Collection();

        foreach ($response as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $result->put($key, $this->transformContentToCollection($value));
            } else {
                $result->put($key, $value);
            }
        }

        return $result;
    }

    /**
     * @param string $method
     * @param array $parameters
     *
     * @return mixed|string
     * @throws Exception
     */
    public function __call($method = 'get', $parameters = [])
    {
        if (in_array(strtoupper($method), ['get', 'post', 'put', 'patch', 'delete'])) {
            $uri = array_shift($parameters);
            $collection = array_pop($parameters);
            $parameters = current($parameters);
            $parameters = is_array($parameters) ? $parameters : [];

            if (! str_contains($uri, 'http')) {
                return $this->callRemote($uri, $method, $parameters, $collection);
            }

            return $this->call($uri, $method, $parameters, $collection);
        }

        throw new Exception('Request method is not accepted.');
    }
}
