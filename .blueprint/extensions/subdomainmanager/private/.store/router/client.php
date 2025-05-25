<?php

use Illuminate\Support\Facades\Route;
use Pterodactyl\Http\Middleware\Activity\ServerSubject;
use Pterodactyl\BlueprintFramework\Extensions\subdomainmanager;
use Pterodactyl\Http\Middleware\Api\Client\Server\ResourceBelongsToServer;
use Pterodactyl\Http\Middleware\Api\Client\Server\AuthenticateServerAccess;

Route::group([
	'prefix' => '/servers/{server}',
	'middleware' => [
		ServerSubject::class,
		AuthenticateServerAccess::class,
		ResourceBelongsToServer::class,
	],
], function () {
	Route::get('/', [subdomainmanager\SubdomainManagerController::class, 'index']);

	Route::post('/', [subdomainmanager\SubdomainManagerController::class, 'store'])->middleware('throttle:5,1');
	Route::delete('/{subdomain}', [subdomainmanager\SubdomainManagerController::class, 'destroy'])->middleware('throttle:5,1');
	Route::patch('/{subdomain}', [subdomainmanager\SubdomainManagerController::class, 'update'])->middleware('throttle:5,1');
});
