<?php

use Pterodactyl\Http\Middleware\Activity\ServerSubject;
use Pterodactyl\Http\Middleware\Api\Client\Server\ResourceBelongsToServer;
use Pterodactyl\Http\Middleware\Api\Client\Server\AuthenticateServerAccess;
use Illuminate\Support\Facades\Route;
use Pterodactyl\BlueprintFramework\Extensions\serverimporter;

Route::group([
	'prefix' => '/servers/{server}',
	'middleware' => [
		ServerSubject::class,
		AuthenticateServerAccess::class,
		ResourceBelongsToServer::class,
	],
], function () {
	Route::group(['prefix' => '/profiles'], function () {
		Route::get('/', [serverimporter\ServerImporterController::class, 'profiles']);

		Route::post('/servers', [serverimporter\ServerImporterController::class, 'storeServerProfile']);
		Route::patch('/servers/{profile}', [serverimporter\ServerImporterController::class, 'updateServerProfile']);
		Route::delete('/servers/{profile}', [serverimporter\ServerImporterController::class, 'deleteServerProfile']);

		Route::post('/credentials', [serverimporter\ServerImporterController::class, 'storeCredentialProfile']);
		Route::patch('/credentials/{profile}', [serverimporter\ServerImporterController::class, 'updateCredentialProfile']);
		Route::delete('/credentials/{profile}', [serverimporter\ServerImporterController::class, 'deleteCredentialProfile']);
	});

	Route::post('/import', [serverimporter\ServerImporterController::class, 'import']);
	Route::post('/test-credentials', [serverimporter\ServerImporterController::class, 'testCredentials']);
});
