<?php

use Illuminate\Http\UploadedFile;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Nest;
use Pterodactyl\Services\Eggs\Sharing\EggImporterService;
use Pterodactyl\Services\Eggs\Sharing\EggUpdateImporterService;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;

$PTERODACTYL_DIRECTORY = env('PTERODACTYL_DIRECTORY');
$EXTENSION_IDENTIFIER = env('EXTENSION_IDENTIFIER');

$base = "$PTERODACTYL_DIRECTORY/.blueprint/extensions/$EXTENSION_IDENTIFIER/private";

$uploadedFile = UploadedFile::createFromBase(
	new SymfonyUploadedFile("$base/egg-server-importer.json", 'egg-server-importer.json')
);

if ($egg = Egg::where('author', 'egg@serverimporter.tld')->first()) {
	app(EggUpdateImporterService::class)->handle($egg, $uploadedFile);
} else {
	app(EggImporterService::class)->handle($uploadedFile, Nest::first()->id);
}
