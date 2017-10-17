#!/bin/bash

if [ -z "$1" ]
	then
		echo "Arg 1: sqlite db"
		exit
fi

if [ -z "$2" ]
	then
		echo "Arg 2: output directory"
		exit
fi

sqlite3 $1 "select subDownloadLink from subtitles where seriesImdbParent = 108778 and subLanguage = 'fre' limit 195" | xargs wget -P $2 -w 2

ls -l $2 | xargs gzip -d

# set downloaded flag
