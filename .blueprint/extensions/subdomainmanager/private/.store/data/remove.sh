#!/bin/bash

echo "Removing Job files..."

rm "$PTERODACTYL_DIRECTORY/app/Jobs/Server/DeleteSubdomainsJob.php"

echo "Removing Job files... Done"

echo "Removing Permissions in Permission.php ..."

sed -i '/\/\/ subdomainmanager$/d' "$PTERODACTYL_DIRECTORY/app/Models/Permission.php"

echo "Removing Permissions in Permission.php ... Done"

echo "Removing Subserver handling in ServerDeletionService.php ..."

sed -i '/\/\/ subdomainmanager$/d' "$PTERODACTYL_DIRECTORY/app/Services/Servers/ServerDeletionService.php"

echo "Removing Subserver handling in ServerDeletionService.php ... Done"