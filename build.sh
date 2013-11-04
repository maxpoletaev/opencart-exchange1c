#!/bin/bash

# This file is build module distributive
# from OpenCart installed module.

INSTALL_PATH="../.."
BUILD_PATH="./upload"

# -------------------------------------------

FILE_LIST=(
	"system/third_party/exchange1c"
	"vqmod/xml/exchange1c.xml"
	"exchange1c/"
	"admin/language/russian/module/exchange1c.php"
	"admin/view/template/module/exchange1c.tpl"
	"admin/controller/module/exchange1c.php"
	"admin/model/module/exchange1c.php"
)

# -------------------------------------------

for FILE in "${FILE_LIST[@]}"; do
	FROM="$INSTALL_PATH/$FILE"
	TO="$BUILD_PATH/$FILE"

	if [ -f $FROM ] || [ -d $FROM ]; then
		mkdir -p $(dirname $TO)
		cp -r $FROM $TO
		echo "[BUILD] $TO"
	else
		echo "[ERROR] File not exists: $FROM"
	fi
done


