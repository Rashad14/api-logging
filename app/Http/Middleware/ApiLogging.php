<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class ApiLogging
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */

    private $logger;

    public function __construct()
    {
        $this->logger = $this->getLogger();
    }

    public function handle(Request $request, Closure $next)
    {
        $this->logger->info('request:');

        $method = $request->method();
        $body = $request->getContent();
        $ip = $request->ip();
        $url = $request->url();
        $id = Auth::user()->id ?? null;
        $email = Auth::user()->email ?? null;
        $queryString = $request->getQueryString();

        $methodUrlString = "$ip $method $url $body $id $email";
        if ($queryString) {
            $methodUrlString .= "?$queryString";
        }

        $this->logger->info(json_encode($methodUrlString));

        return $next($request);
    }

    public function terminate(Request $request, Response $response)
    {
        $this->logger->info('response:');
        $this->logger->info(json_encode($response));
    }

    private function getLogger()
    {
        $dateString = now()->format('Y-m-d');
        $yearMonth = now()->format('Y-m');
        $filePath = 'api-logging/' . $yearMonth . '/' . $dateString . '/'. 'POST' .'__'. 'logging' .'.'. 'log';

        $formatter = new JsonFormatter;
        $stream = new StreamHandler(storage_path('logs/' . $filePath), Logger::DEBUG);
        $stream->setFormatter($formatter);

        $processId = Str::random(5);
        $logger = new Logger($processId);

        $logger->pushHandler($stream);

        return $logger;
    }
}
