#!/bin/bash

DIRECTORY="$PTERODACTYL_DIRECTORY/.blueprint/extensions/$EXTENSION_IDENTIFIER/private"

echo "Copying Job files..."

mkdir -p "$PTERODACTYL_DIRECTORY/app/Jobs/Server"
cp -f "$DIRECTORY/DeleteSubdomainsJob.php" "$PTERODACTYL_DIRECTORY/app/Jobs/Server/DeleteSubdomainsJob.php"

echo "Copying Job files... Done"

## bpcompat
echo "Removing Permissions in Permission.php ..."

sed -i '/\/\/ subdomainmanager$/d' "$PTERODACTYL_DIRECTORY/app/Models/Permission.php"

echo "Removing Permissions in Permission.php ... Done"
## bpcompat end

if grep -q "// subdomainmanager" "$PTERODACTYL_DIRECTORY/app/Models/Permission.php"; then
	echo "Permissions already added in Permission.php ... Skipping"
else
	echo "Adding Permissions in Permission.php ..."

	INPUT=$(cat "$PTERODACTYL_DIRECTORY/app/Models/Permission.php")
	INSERT_LINE=$(echo "$INPUT" | grep -n "'docker-image' => '" | cut -f1 -d:)
	INSERT_LINE=$((INSERT_LINE + 3))
	LINE_COUNT=$(echo "$INPUT" | wc -l)
	SECOND_HALF_COUNT=$((LINE_COUNT - INSERT_LINE))

	FRAGMENT="

			'subdomains' => [ // subdomainmanager
					'description' => 'Permissions that control a user\'s ability to manage subdomains for this server.', // subdomainmanager
					'keys' => [ // subdomainmanager
							'read' => 'Allows a user to view the created subdomains.', // subdomainmanager
							'create' => 'Allows a user to create a new server subdomain.', // subdomainmanager
							'update' => 'Allows a user to update the server subdomains.', // subdomainmanager
							'delete' => 'Allows a user to delete an existing server subdomain.', // subdomainmanager
					], // subdomainmanager
			], // subdomainmanager

	"

	FIRST_HALF=$(echo "$INPUT" | head -n $INSERT_LINE)
	SECOND_HALF=$(echo "$INPUT" | tail -n $SECOND_HALF_COUNT)
	OUTPUT="${FIRST_HALF}${FRAGMENT}${SECOND_HALF}"
	echo "$OUTPUT" > "$PTERODACTYL_DIRECTORY/app/Models/Permission.php"

	echo "Adding Permissions in Permission.php ... Done"
fi

if grep -q "// subdomainmanager" "$PTERODACTYL_DIRECTORY/app/Services/Servers/ServerDeletionService.php"; then
	echo "Subdomain handling already added in ServerDeletionService.php ... Skipping"
else
	echo "Adding Subserver handling in ServerDeletionService.php ..."

	INPUT=$(cat "$PTERODACTYL_DIRECTORY/app/Services/Servers/ServerDeletionService.php")
	INSERT_LINE=$(echo "$INPUT" | grep -n "public function handle(Server \$server): void" | cut -f1 -d:)
	INSERT_LINE=$((INSERT_LINE + 1))
	LINE_COUNT=$(echo "$INPUT" | wc -l)
	SECOND_HALF_COUNT=$((LINE_COUNT - INSERT_LINE))

	FRAGMENT="
        \\Pterodactyl\\Jobs\\Server\\DeleteSubdomainsJob::dispatch(\\Illuminate\\Support\\Facades\\DB::table('server_subdomains')->where('server_id', \$server->id)->pluck('id')->toArray()); // subdomainmanager
"

	FIRST_HALF=$(echo "$INPUT" | head -n $INSERT_LINE)
	SECOND_HALF=$(echo "$INPUT" | tail -n $SECOND_HALF_COUNT)
	OUTPUT="${FIRST_HALF}${FRAGMENT}${SECOND_HALF}"
	echo "$OUTPUT" > "$PTERODACTYL_DIRECTORY/app/Services/Servers/ServerDeletionService.php"

	echo "Adding Subserver handling in ServerDeletionService.php ... Done"
fi