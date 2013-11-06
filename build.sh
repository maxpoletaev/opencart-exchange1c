#!/bin/bash

INSTALL_PATH="../.."
BUILD_PATH="./upload"

# -------------------------------------------

FILE_LIST=(
	"admin/language/russian/module/exchange1c.php"
	"admin/view/template/module/exchange1c.tpl"
	"admin/controller/module/exchange1c.php"
	"admin/model/module/exchange1c.php"
	"system/third_party/exchange1c"
	"vqmod/xml/exchange1c.xml"
	"exchange1c/"
)

# -------------------------------------------

for FILE in "${FILE_LIST[@]}"; do
	FROM="$INSTALL_PATH/$FILE"
	TO="$BUILD_PATH/$FILE"

	if [ -f $FROM ] || [ -d $FROM ]; then
		if [ ! -d $(dirname $TO) ]; then
			mkdir -p $(dirname $TO)
		fi
		cp -aurT $FROM $TO
	else
		"No such file or directory: $FROM"
	fi
done


