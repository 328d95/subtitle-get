#!/bin/bash

PAGE_SIZE=100

if [ $# -lt 2 ]; then
	echo "page limits are required"
	exit
fi

if [ -z "$3" ]
	then
		echo "output directory required"
		exit
fi

if [ ! -d $3 ]
	then
		mkdir -p $3;
fi

for i in $(seq $1 $2) 
	do
		wget -qO- -A.html "http://www.imdb.com/search/title?primary_language=fr&sort=moviemeter,asc&page=$i&count=$PAGE_SIZE" | sed -n 's/.*title\/tt\([0-9]*\).*/\1/p' | sort | uniq | tr '\n' ',' > "$3/p$i-$PAGE_SIZE-imdbids.txt"
	sleep 1
	done
