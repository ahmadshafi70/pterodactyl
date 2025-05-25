<?php

use Illuminate\Support\Facades\Route;
use Pterodactyl\Http\Controllers\Admin;

Route::group(['prefix' => 'extensions/serverimporter'], function () {
	Route::get('/', [Admin\Extensions\serverimporter\serverimporterExtensionController::class, 'index'])->name('admin.extensions.serverimporter.index');
	Route::patch('/', [Admin\Extensions\serverimporter\serverimporterExtensionController::class, 'update'])->name('admin.extensions.serverimporter.patch');
	Route::post('/', [Admin\Extensions\serverimporter\serverimporterExtensionController::class, 'post'])->name('admin.extensions.serverimporter.post');
	Route::put('/', [Admin\Extensions\serverimporter\serverimporterExtensionController::class, 'put'])->name('admin.extensions.serverimporter.put');
	Route::delete('/{target}/{id}', [Admin\Extensions\serverimporter\serverimporterExtensionController::class, 'delete'])->name('admin.extensions.serverimporter.delete');
});