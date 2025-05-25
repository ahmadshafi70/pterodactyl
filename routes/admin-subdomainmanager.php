<?php

use Illuminate\Support\Facades\Route;
use Pterodactyl\Http\Controllers\Admin;

Route::group(['prefix' => 'extensions/subdomainmanager'], function () {
	Route::get('/', [Admin\Extensions\subdomainmanager\subdomainmanagerExtensionController::class, 'index'])->name('admin.extensions.subdomainmanager.index');
	Route::patch('/', [Admin\Extensions\subdomainmanager\subdomainmanagerExtensionController::class, 'update'])->name('admin.extensions.subdomainmanager.patch');
	Route::post('/', [Admin\Extensions\subdomainmanager\subdomainmanagerExtensionController::class, 'post'])->name('admin.extensions.subdomainmanager.post');
	Route::put('/', [Admin\Extensions\subdomainmanager\subdomainmanagerExtensionController::class, 'put'])->name('admin.extensions.subdomainmanager.put');
	Route::delete('/{target}/{id}', [Admin\Extensions\subdomainmanager\subdomainmanagerExtensionController::class, 'delete'])->name('admin.extensions.subdomainmanager.delete');
});