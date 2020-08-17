<?php

/*use App\Middleware\AuthMiddleware;
use App\Middleware\GuestMiddleware;*/

// 

// Creating routes.

// Documentation home.
$app->get('/', 'staticPages:home');

// Documentation entry point.
$app->get('/doc', function($request, $response, $args) {

    $swagger = \Swagger\scan([__DIR__]);

    $response = $response->withHeader('Content-Type', 'application/json');
    $response = $response->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization');
    $response = $response->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
    $response = $response->withHeader('Access-Control-Allow-Origin', '*');

    echo $swagger;
    return $response;

});

// Versioning group
$app->group('/v1', function() use ($container) {

    // Files metadata: get metadata.
    $this->group('/metadata', function () use ($container) {

        // Getting the metadata for all the files associated to a vre_id.
        $this->get('', 'apiController:get_user_resources');

        // Post metadata from Catalogue portal (fileID).
        $this->post('', 'apiController:set_user_files');

    })->add(new App\Middleware\JsonResponse($container));

})->add(new App\Middleware\TokenVerify($container));
//});