<?php

namespace Killtw\Api;

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
     *
     * @return mixed|string
     */
    public function call($uri, $method, $parameters = [])
    {
        $method = strtoupper($method);

        try {
            $request = $this->request->create($uri, $method, $parameters);

            $dispatch = $this->router->dispatch($request);

            $response = $dispatch->getContent();

            if ($dispatch->headers->get('CONTENT-TYPE') == 'application/json') {
                $response = json_decode($response);
            }

            return $response;
        } catch (NotFoundHttpException $e) {
            throw new NotFoundHttpException('Request Not Found.');
        }
    }

    /**
     * @param $uri
     * @param $method
     * @param array $parameters
     *
     * @return mixed|string
     */
    public function callRemote($uri, $method, $parameters= [])
    {
        $client = $this->client;

        try {
            $request = $client->request($method, $uri, $parameters);
            $response = $request->getBody()->getContents();

            if ($request->getHeader('CONTENT-TYPE') == 'application/json') {
                $response = json_decode($response);
            }

            return $response;
        } catch (RequestException $e) {
            throw new NotFoundHttpException('Request Not Found.');
        }
    }

    /**
     * @param string $method
     * @param array $parameters
     *
     * @return mixed|string
     * @throws \Exception
     */
    public function __call($method = 'get', $parameters = [])
    {
        if (in_array($method, ['get', 'post', 'put', 'patch', 'delete'])) {
            $uri = array_shift($parameters);
            $parameters = current($parameters);
            $parameters = is_array($parameters) ? $parameters : [];

            if (! str_contains($uri, 'http')) {
                return $this->callRemote($uri, $method, $parameters);
            }

            return $this->call($uri, $method, $parameters);
        }

        throw new \Exception('Request method is not accepted.');
    }
}
