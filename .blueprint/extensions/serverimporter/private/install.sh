#!/bin/bash

DIRECTORY="$PTERODACTYL_DIRECTORY/.blueprint/extensions/$EXTENSION_IDENTIFIER/private"

echo "Copying Job files..."

mkdir -p "$PTERODACTYL_DIRECTORY/app/Jobs/Server"
cp -f "$DIRECTORY/ImportServerJob.php" "$PTERODACTYL_DIRECTORY/app/Jobs/Server/ImportServerJob.php"

echo "Copying Job files... Done"

echo "Adding or Updating Egg..."

mkdir -p /tmp/psysh
echo "
<?php

return [
	'runtimeDir' => '~/tmp'
];" > /tmp/psysh/config.php

XDG_CONFIG_HOME=/tmp php artisan tinker -n "$DIRECTORY/upsertEgg.php"

echo "Adding or Updating Egg... Done"