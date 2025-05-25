<?php

header('Content-Type: application/json');

echo(json_encode([
	'38dfd3d0dc9852beadfe778daf237e33:176134' => [
		'version' => '{version}',
		'engine' => '{engine}',
		'timestamp' => {timestamp},
		'target' => '{target}',
	]
]));