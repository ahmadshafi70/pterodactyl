<?php

use Illuminate\Support\Facades\Route;
use Pterodactyl\Http\Controllers\Admin;
use Pterodactyl\Http\Middleware\Admin\Servers\ServerInstalled;

Route::get('/', [Admin\BaseController::class, 'index'])->name('admin.index');

/*
|--------------------------------------------------------------------------
| Theme Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/theme
|
*/
Route::group(['prefix' => 'arix'], function () {
    Route::get('/', [Admin\Arix\ArixController::class, 'index'])->name('admin.arix');
    Route::post('/', [Admin\Arix\ArixController::class, 'store']);

    Route::get('/layout', [Admin\Arix\ArixLayoutController::class, 'index'])->name('admin.arix.layout');
    Route::post('/layout', [Admin\Arix\ArixLayoutController::class, 'store']);

    Route::get('/components', [Admin\Arix\ArixComponentsController::class, 'index'])->name('admin.arix.components');
    Route::post('/components', [Admin\Arix\ArixComponentsController::class, 'store']);

    Route::get('/announcement', [Admin\Arix\ArixAnnouncementController::class, 'index'])->name('admin.arix.announcement');
    Route::post('/announcement', [Admin\Arix\ArixAnnouncementController::class, 'store']);

    Route::get('/mail', [Admin\Arix\ArixMailController::class, 'index'])->name('admin.arix.mail');
    Route::post('/mail', [Admin\Arix\ArixMailController::class, 'store']);

    Route::get('/styling', [Admin\Arix\ArixStylingController::class, 'index'])->name('admin.arix.styling');
    Route::post('/styling', [Admin\Arix\ArixStylingController::class, 'store']);

    Route::get('/meta', [Admin\Arix\ArixMetaController::class, 'index'])->name('admin.arix.meta');
    Route::post('/meta', [Admin\Arix\ArixMetaController::class, 'store']);

    Route::get('/colors', [Admin\Arix\ArixColorsController::class, 'index'])->name('admin.arix.colors');
    Route::post('/colors', [Admin\Arix\ArixColorsController::class, 'store']);

    Route::get('/advanced', [Admin\Arix\ArixAdvancedController::class, 'index'])->name('admin.arix.advanced');
    Route::post('/advanced', [Admin\Arix\ArixAdvancedController::class, 'store']);
});

/*
|--------------------------------------------------------------------------
| Location Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/api
|
*/
Route::group(['prefix' => 'api'], function () {
    Route::get('/', [Admin\ApiController::class, 'index'])->name('admin.api.index');
    Route::get('/new', [Admin\ApiController::class, 'create'])->name('admin.api.new');

    Route::post('/new', [Admin\ApiController::class, 'store']);

    Route::delete('/revoke/{identifier}', [Admin\ApiController::class, 'delete'])->name('admin.api.delete');
});

/*
|--------------------------------------------------------------------------
| Location Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/locations
|
*/
Route::group(['prefix' => 'locations'], function () {
    Route::get('/', [Admin\LocationController::class, 'index'])->name('admin.locations');
    Route::get('/view/{location:id}', [Admin\LocationController::class, 'view'])->name('admin.locations.view');

    Route::post('/', [Admin\LocationController::class, 'create']);
    Route::patch('/view/{location:id}', [Admin\LocationController::class, 'update']);
});

/*
|--------------------------------------------------------------------------
| Database Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/databases
|
*/
Route::group(['prefix' => 'databases'], function () {
    Route::get('/', [Admin\DatabaseController::class, 'index'])->name('admin.databases');
    Route::get('/view/{host:id}', [Admin\DatabaseController::class, 'view'])->name('admin.databases.view');

    Route::post('/', [Admin\DatabaseController::class, 'create']);
    Route::patch('/view/{host:id}', [Admin\DatabaseController::class, 'update']);
    Route::delete('/view/{host:id}', [Admin\DatabaseController::class, 'delete']);
});

/*
|--------------------------------------------------------------------------
| Settings Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/settings
|
*/
Route::group(['prefix' => 'settings'], function () {
    Route::get('/', [Admin\Settings\IndexController::class, 'index'])->name('admin.settings');
    Route::get('/mail', [Admin\Settings\MailController::class, 'index'])->name('admin.settings.mail');
    Route::get('/advanced', [Admin\Settings\AdvancedController::class, 'index'])->name('admin.settings.advanced');

    Route::post('/mail/test', [Admin\Settings\MailController::class, 'test'])->name('admin.settings.mail.test');

    Route::patch('/', [Admin\Settings\IndexController::class, 'update']);
    Route::patch('/mail', [Admin\Settings\MailController::class, 'update']);
    Route::patch('/advanced', [Admin\Settings\AdvancedController::class, 'update']);
});

/*
|--------------------------------------------------------------------------
| User Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/users
|
*/
Route::group(['prefix' => 'users'], function () {
    Route::get('/', [Admin\UserController::class, 'index'])->name('admin.users');
    Route::get('/accounts.json', [Admin\UserController::class, 'json'])->name('admin.users.json');
    Route::get('/new', [Admin\UserController::class, 'create'])->name('admin.users.new');
    Route::get('/view/{user:id}', [Admin\UserController::class, 'view'])->name('admin.users.view');

    Route::post('/new', [Admin\UserController::class, 'store']);

    Route::patch('/view/{user:id}', [Admin\UserController::class, 'update']);
    Route::delete('/view/{user:id}', [Admin\UserController::class, 'delete']);
});

/*
|--------------------------------------------------------------------------
| Server Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/servers
|
*/
Route::group(['prefix' => 'servers'], function () {
    Route::get('/', [Admin\Servers\ServerController::class, 'index'])->name('admin.servers');
    Route::get('/new', [Admin\Servers\CreateServerController::class, 'index'])->name('admin.servers.new');
    Route::get('/view/{server:id}', [Admin\Servers\ServerViewController::class, 'index'])->name('admin.servers.view');

    Route::group(['middleware' => [ServerInstalled::class]], function () {
        Route::get('/view/{server:id}/details', [Admin\Servers\ServerViewController::class, 'details'])->name('admin.servers.view.details');
        Route::get('/view/{server:id}/build', [Admin\Servers\ServerViewController::class, 'build'])->name('admin.servers.view.build');
        Route::get('/view/{server:id}/startup', [Admin\Servers\ServerViewController::class, 'startup'])->name('admin.servers.view.startup');
        Route::get('/view/{server:id}/database', [Admin\Servers\ServerViewController::class, 'database'])->name('admin.servers.view.database');
        Route::get('/view/{server:id}/mounts', [Admin\Servers\ServerViewController::class, 'mounts'])->name('admin.servers.view.mounts');
    });

    Route::get('/view/{server:id}/manage', [Admin\Servers\ServerViewController::class, 'manage'])->name('admin.servers.view.manage');
    Route::get('/view/{server:id}/delete', [Admin\Servers\ServerViewController::class, 'delete'])->name('admin.servers.view.delete');

    Route::post('/new', [Admin\Servers\CreateServerController::class, 'store']);
    Route::post('/view/{server:id}/build', [Admin\ServersController::class, 'updateBuild']);
    Route::post('/view/{server:id}/startup', [Admin\ServersController::class, 'saveStartup']);
    Route::post('/view/{server:id}/database', [Admin\ServersController::class, 'newDatabase']);
    Route::post('/view/{server:id}/mounts', [Admin\ServersController::class, 'addMount'])->name('admin.servers.view.mounts.store');
    Route::post('/view/{server:id}/manage/toggle', [Admin\ServersController::class, 'toggleInstall'])->name('admin.servers.view.manage.toggle');
    Route::post('/view/{server:id}/manage/suspension', [Admin\ServersController::class, 'manageSuspension'])->name('admin.servers.view.manage.suspension');
    Route::post('/view/{server:id}/manage/reinstall', [Admin\ServersController::class, 'reinstallServer'])->name('admin.servers.view.manage.reinstall');
    Route::post('/view/{server:id}/manage/transfer', [Admin\Servers\ServerTransferController::class, 'transfer'])->name('admin.servers.view.manage.transfer');
    Route::post('/view/{server:id}/delete', [Admin\ServersController::class, 'delete']);

    Route::patch('/view/{server:id}/details', [Admin\ServersController::class, 'setDetails']);
    Route::patch('/view/{server:id}/database', [Admin\ServersController::class, 'resetDatabasePassword']);

    Route::delete('/view/{server:id}/database/{database:id}/delete', [Admin\ServersController::class, 'deleteDatabase'])->name('admin.servers.view.database.delete');
    Route::delete('/view/{server:id}/mounts/{mount:id}', [Admin\ServersController::class, 'deleteMount'])
        ->name('admin.servers.view.mounts.delete');
});

/*
|--------------------------------------------------------------------------
| Node Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/nodes
|
*/
Route::group(['prefix' => 'nodes'], function () {
    Route::get('/', [Admin\Nodes\NodeController::class, 'index'])->name('admin.nodes');
    Route::get('/new', [Admin\NodesController::class, 'create'])->name('admin.nodes.new');
    Route::get('/view/{node:id}', [Admin\Nodes\NodeViewController::class, 'index'])->name('admin.nodes.view');
    Route::get('/view/{node:id}/settings', [Admin\Nodes\NodeViewController::class, 'settings'])->name('admin.nodes.view.settings');
    Route::get('/view/{node:id}/configuration', [Admin\Nodes\NodeViewController::class, 'configuration'])->name('admin.nodes.view.configuration');
    Route::get('/view/{node:id}/allocation', [Admin\Nodes\NodeViewController::class, 'allocations'])->name('admin.nodes.view.allocation');
    Route::get('/view/{node:id}/servers', [Admin\Nodes\NodeViewController::class, 'servers'])->name('admin.nodes.view.servers');
    Route::get('/view/{node:id}/system-information', Admin\Nodes\SystemInformationController::class);

    Route::post('/new', [Admin\NodesController::class, 'store']);
    Route::post('/view/{node:id}/allocation', [Admin\NodesController::class, 'createAllocation']);
    Route::post('/view/{node:id}/allocation/remove', [Admin\NodesController::class, 'allocationRemoveBlock'])->name('admin.nodes.view.allocation.removeBlock');
    Route::post('/view/{node:id}/allocation/alias', [Admin\NodesController::class, 'allocationSetAlias'])->name('admin.nodes.view.allocation.setAlias');
    Route::post('/view/{node:id}/settings/token', Admin\NodeAutoDeployController::class)->name('admin.nodes.view.configuration.token');

    Route::patch('/view/{node:id}/settings', [Admin\NodesController::class, 'updateSettings']);

    Route::delete('/view/{node:id}/delete', [Admin\NodesController::class, 'delete'])->name('admin.nodes.view.delete');
    Route::delete('/view/{node:id}/allocation/remove/{allocation:id}', [Admin\NodesController::class, 'allocationRemoveSingle'])->name('admin.nodes.view.allocation.removeSingle');
    Route::delete('/view/{node:id}/allocations', [Admin\NodesController::class, 'allocationRemoveMultiple'])->name('admin.nodes.view.allocation.removeMultiple');
});

/*
|--------------------------------------------------------------------------
| Mount Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/mounts
|
*/
Route::group(['prefix' => 'mounts'], function () {
    Route::get('/', [Admin\MountController::class, 'index'])->name('admin.mounts');
    Route::get('/view/{mount:id}', [Admin\MountController::class, 'view'])->name('admin.mounts.view');

    Route::post('/', [Admin\MountController::class, 'create']);
    Route::post('/{mount:id}/eggs', [Admin\MountController::class, 'addEggs'])->name('admin.mounts.eggs');
    Route::post('/{mount:id}/nodes', [Admin\MountController::class, 'addNodes'])->name('admin.mounts.nodes');

    Route::patch('/view/{mount:id}', [Admin\MountController::class, 'update']);

    Route::delete('/{mount:id}/eggs/{egg_id}', [Admin\MountController::class, 'deleteEgg']);
    Route::delete('/{mount:id}/nodes/{node_id}', [Admin\MountController::class, 'deleteNode']);
});

/*
|--------------------------------------------------------------------------
| Nest Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/nests
|
*/
Route::group(['prefix' => 'nests'], function () {
    Route::get('/', [Admin\Nests\NestController::class, 'index'])->name('admin.nests');
    Route::get('/new', [Admin\Nests\NestController::class, 'create'])->name('admin.nests.new');
    Route::get('/view/{nest:id}', [Admin\Nests\NestController::class, 'view'])->name('admin.nests.view');
    Route::get('/egg/new', [Admin\Nests\EggController::class, 'create'])->name('admin.nests.egg.new');
    Route::get('/egg/{egg:id}', [Admin\Nests\EggController::class, 'view'])->name('admin.nests.egg.view');
    Route::get('/egg/{egg:id}/export', [Admin\Nests\EggShareController::class, 'export'])->name('admin.nests.egg.export');
    Route::get('/egg/{egg:id}/variables', [Admin\Nests\EggVariableController::class, 'view'])->name('admin.nests.egg.variables');
    Route::get('/egg/{egg:id}/scripts', [Admin\Nests\EggScriptController::class, 'index'])->name('admin.nests.egg.scripts');

    Route::post('/new', [Admin\Nests\NestController::class, 'store']);
    Route::post('/import', [Admin\Nests\EggShareController::class, 'import'])->name('admin.nests.egg.import');
    Route::post('/egg/new', [Admin\Nests\EggController::class, 'store']);
    Route::post('/egg/{egg:id}/variables', [Admin\Nests\EggVariableController::class, 'store']);

    Route::put('/egg/{egg:id}', [Admin\Nests\EggShareController::class, 'update']);

    Route::patch('/view/{nest:id}', [Admin\Nests\NestController::class, 'update']);
    Route::patch('/egg/{egg:id}', [Admin\Nests\EggController::class, 'update']);
    Route::patch('/egg/{egg:id}/scripts', [Admin\Nests\EggScriptController::class, 'update']);
    Route::patch('/egg/{egg:id}/variables/{variable:id}', [Admin\Nests\EggVariableController::class, 'update'])->name('admin.nests.egg.variables.edit');

    Route::delete('/view/{nest:id}', [Admin\Nests\NestController::class, 'destroy']);
    Route::delete('/egg/{egg:id}', [Admin\Nests\EggController::class, 'destroy']);
    Route::delete('/egg/{egg:id}/variables/{variable:id}', [Admin\Nests\EggVariableController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| Automatic phpMyAdmin Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/automatic-phpmyadmin/
|
*/
Route::group(['prefix' => 'automatic-phpmyadmin'], function () {
    Route::get('/', [Admin\AutomaticPhpMyAdminController::class, 'index'])->name('admin.automatic-phpmyadmin');
    Route::get('/new', [Admin\AutomaticPhpMyAdminController::class, 'create'])->name('admin.automatic-phpmyadmin.new');
    Route::get('/view/{automaticphpmyadmin:id}', [Admin\AutomaticPhpMyAdminController::class, 'view'])->name('admin.automatic-phpmyadmin.view');

    Route::post('/new', [Admin\AutomaticPhpMyAdminController::class, 'store']);

    Route::patch('/view/{automaticphpmyadmin:id}', [Admin\AutomaticPhpMyAdminController::class, 'update']);

    Route::delete('/delete/{automaticphpmyadmin:id}', [Admin\AutomaticPhpMyAdminController::class, 'destroy'])->name('admin.automatic-phpmyadmin.delete');
});

/*
|--------------------------------------------------------------------------
| Node Backup Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/node-backup/
|
*/
Route::group(['prefix' => 'node-backup'], function() {
    Route::get('/', [Admin\NodeBackup\NodeBackupController::class, 'index'])->name('admin.node-backup');
    Route::get('/statistics', [Admin\NodeBackup\NodeBackupController::class, 'statistics'])->name('admin.node-backup.statistics');
    Route::group(['prefix' => 'group'], function() {
        Route::get('/new', [Admin\NodeBackup\NodeBackupController::class, 'createNodeBackupGroup'])->name('admin.node-backup.group.new');
        Route::post('/new', [Admin\NodeBackup\NodeBackupController::class, 'storeNodeBackupGroup'])->name('admin.node-backup.group.store');
        Route::group(['prefix' => '{nodeBackupGroupId}'], function() {
            Route::get('/', [Admin\NodeBackup\NodeBackupController::class, 'viewNodeBackupGroup'])->name('admin.node-backup.group.view');
            Route::get('/edit', [Admin\NodeBackup\NodeBackupController::class, 'editNodeBackupGroup'])->name('admin.node-backup.group.edit');
            Route::patch('/edit', [Admin\NodeBackup\NodeBackupController::class, 'updateNodeBackupGroup'])->name('admin.node-backup.group.update');
            Route::delete('/delete', [Admin\NodeBackup\NodeBackupController::class, 'destroyNodeBackupGroup'])->name('admin.node-backup.group.delete');
            Route::group(['prefix' => 'backup'], function() {
                Route::get('/new', [Admin\NodeBackup\NodeBackupController::class, 'createNodeBackup'])->name('admin.node-backup.group.backup.new');
                Route::post('/new', [Admin\NodeBackup\NodeBackupController::class, 'storeNodeBackup'])->name('admin.node-backup.group.backup.store');
                Route::group(['prefix' => '{nodeBackupId}'], function() {
                    Route::get('/', [Admin\NodeBackup\NodeBackupController::class, 'viewNodeBackup'])->name('admin.node-backup.group.backup.view');
                    Route::get('/restore', [Admin\NodeBackup\NodeBackupController::class, 'restoreNodeBackup'])->name('admin.node-backup.group.backup.restore');
                    Route::get('/restore-on-another-node/{nodeId}', [Admin\NodeBackup\NodeBackupController::class, 'restoreNodeBackupOnAnotherNode'])->name('admin.node-backup.group.backup.restore-on-another-node');
                    Route::get('/stop', [Admin\NodeBackup\NodeBackupController::class, 'stopNodeBackup'])->name('admin.node-backup.group.backup.stop');
                    Route::get('/try-again', [Admin\NodeBackup\NodeBackupController::class, 'tryAgainNodeBackup'])->name('admin.node-backup.group.backup.try-again');
                    Route::delete('/delete', [Admin\NodeBackup\NodeBackupController::class, 'destroyNodeBackup'])->name('admin.node-backup.group.backup.delete');
                    Route::group(['prefix' => '/server-backup/{nodeBackupServerId}'], function() {
                        Route::get('/try-again', [Admin\NodeBackup\NodeBackupController::class, 'tryAgainNodeBackupServer'])->name('admin.node-backup.group.backup.server.try-again');
                        Route::get('/download', [Admin\NodeBackup\NodeBackupController::class, 'downloadNodeBackupServer'])->name('admin.node-backup.group.backup.server.download');
                        Route::get('/restore', [Admin\NodeBackup\NodeBackupController::class, 'restoreNodeBackupServer'])->name('admin.node-backup.group.backup.server.restore');
                        Route::get('/restore-on-another-node/{nodeId}', [Admin\NodeBackup\NodeBackupController::class, 'restoreNodeBackupServerOnAnotherNode'])->name('admin.node-backup.group.backup.server.restore-on-another-node');
                    });
                });
            });
        });
    });
    Route::group(['prefix' => 's3-server'], function () {
        Route::get('/', [Admin\NodeBackup\NodeBackupS3ServerController::class, 'index'])->name('admin.node-backup.s3-server');
        Route::get('/new', [Admin\NodeBackup\NodeBackupS3ServerController::class, 'create'])->name('admin.node-backup.s3-server.new');
        Route::post('/new', [Admin\NodeBackup\NodeBackupS3ServerController::class, 'store'])->name('admin.node-backup.s3-server.store');
        Route::group(['prefix' => '{nodeBackupS3ServerId}'], function() {
            Route::get('/', [Admin\NodeBackup\NodeBackupS3ServerController::class, 'view'])->name('admin.node-backup.s3-server.view');
            Route::patch('/', [Admin\NodeBackup\NodeBackupS3ServerController::class, 'update'])->name('admin.node-backup.s3-server.update');
            Route::delete('/', [Admin\NodeBackup\NodeBackupS3ServerController::class, 'destroy'])->name('admin.node-backup.s3-server.delete');
        });
    });
});
include 'admin-subdomainmanager.php';
include 'admin-versionchanger.php';
include 'admin-serverimporter.php';