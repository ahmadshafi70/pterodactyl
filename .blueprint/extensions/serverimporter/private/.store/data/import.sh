#!/bin/bash

mkdir -p /mnt/server
cd /mnt/server

# Install required dependencies
apt-get update
apt-get install -y lftp

# Basic Script that imports from an sftp/ftp server
MODE=$IMPORT_MODE # sftp or ftp
HOST=$IMPORT_HOST
PORT=${IMPORT_PORT:-22} # Default to port 22 if not set
USER=$IMPORT_USERNAME
PASSWORD=$IMPORT_PASSWORD
FROM_DIR=$IMPORT_FROM_PATH
TO_DIR=$IMPORT_TO_PATH

recursive_mkdir() {
	local dir=$1
	if [ -d "$dir" ]; then
		echo "Directory $dir already exists."
	else
		recursive_mkdir "$(dirname "$dir")"
		echo "Creating directory $dir..."
		mkdir "$dir"
	fi
}

# Function for recursive SFTP transfer using lftp
transfer_sftp() {
	echo "Starting SFTP recursive transfer from $FROM_DIR to $TO_DIR..."

	# Create the target directory if it doesn't exist
	recursive_mkdir $TO_DIR
	cd $TO_DIR

	lftp -u "$USER,$PASSWORD" -p "$PORT" -e "set sftp:connect-program 'ssh -o StrictHostKeyChecking=no'; mirror --verbose -c --use-pget-n=10 $FROM_DIR; bye" sftp://$HOST

	if [ "$FROM_DIR" != "./" ]; then
		mv $FROM_DIR/* .
		mv $FROM_DIR/.* .
		rm -rf $FROM_DIR
	fi
}

# Function for FTP transfer
transfer_ftp() {
	echo "Starting FTP transfer from $FROM_DIR to $TO_DIR..."

	# Create the target directory if it doesn't exist
	recursive_mkdir $TO_DIR
	cd $TO_DIR

	lftp -u "$USER,$PASSWORD" -e "mirror --verbose -c --use-pget-n=10 $FROM_DIR $TO_DIR; bye" ftp://$HOST

	if [ "$FROM_DIR" != "./" ]; then
		mv $FROM_DIR/* .
		mv $FROM_DIR/.* .
		rm -rf $FROM_DIR
	fi
}

# Perform the transfer based on the mode
case $MODE in
	sftp)
		transfer_sftp
		;;
	ftp)
		transfer_ftp
		;;
	*)
		echo "Error: Unsupported mode '$MODE'. Use 'sftp' or 'ftp'."
		exit 1
		;;
esac

echo "Transfer complete."