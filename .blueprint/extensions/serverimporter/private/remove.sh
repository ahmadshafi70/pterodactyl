#!/bin/bash

DIRECTORY="$PTERODACTYL_DIRECTORY/.blueprint/extensions/$EXTENSION_IDENTIFIER/private"

echo "Removing Job files..."

rm "$PTERODACTYL_DIRECTORY/app/Jobs/Server/ImportServerJob.php"

echo "Removing Job files... Done"

echo "Removing Egg..."

mkdir -p /tmp/psysh
echo "
<?php

return [
	'runtimeDir' => '~/tmp'
];" > /tmp/psysh/config.php

XDG_CONFIG_HOME=/tmp php artisan tinker -n "$DIRECTORY/removeEgg.php"

echo "Removing Egg... Done"