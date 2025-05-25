<?php

use Illuminate\Support\Facades\Route;
use Pterodactyl\Http\Controllers\Admin;

Route::group(['prefix' => 'extensions/versionchanger'], function () {
	Route::get('/', [Admin\Extensions\versionchanger\versionchangerExtensionController::class, 'index'])->name('admin.extensions.versionchanger.index');
	Route::patch('/', [Admin\Extensions\versionchanger\versionchangerExtensionController::class, 'update'])->name('admin.extensions.versionchanger.patch');
	Route::post('/', [Admin\Extensions\versionchanger\versionchangerExtensionController::class, 'post'])->name('admin.extensions.versionchanger.post');
	Route::put('/', [Admin\Extensions\versionchanger\versionchangerExtensionController::class, 'put'])->name('admin.extensions.versionchanger.put');
	Route::delete('/{target}/{id}', [Admin\Extensions\versionchanger\versionchangerExtensionController::class, 'delete'])->name('admin.extensions.versionchanger.delete');
});