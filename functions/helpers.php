<?php

use NormanHuth\SymfonyBladeTemplates\SymfonyView;
use Symfony\Component\HttpFoundation\Response;

if (!function_exists('view')) {
    /**
     * Get the evaluated view contents for the given view and create a new HTTP response.
     *
     * @param string $view
     * @param array  $data
     * @param array  $mergeData
     * @param int    $status
     * @param array  $headers
     * @return Response
     */
    function view(string $view, array $data = [], array $mergeData = [], int $status = 200, array $headers = []): Response
    {
        return (new SymfonyView())->response($view, $data, $mergeData, $status, $headers);
    }
}
