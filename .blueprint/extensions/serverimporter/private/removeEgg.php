<?php

use Pterodactyl\Models\Egg;

$egg = Egg::where('author', 'egg@serverimporter.tld')->first();

if ($egg) {
	$serversCount = $egg->servers()->count();

	if ($serversCount === 0) {
		$egg->delete();
	}
}