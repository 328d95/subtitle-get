#!/bin/bash

for link in $(wget -qO- -A.html "http://www.imdb.com/list/ls025718175/?publish=publish" | sed -n 's/.*title\/tt\([0-9]*\).*/\1/p' | sort | uniq | sed 's/.*/www\.imdb\.com\/title\/tt\0\/episodes/')
	do
		#echo $link
		first=$(wget -qO- -A.html "$link")
		seasons=$(echo "$first" | sed -n 's/.*option selected.*value=\"\([0-9]*\).*/\1/p')
		echo $(echo "$first" | sed -n 's/.*title\/tt\([0-9]*\).*/\1/p' | sort | uniq | tr "\n" ",")
		if [[ $seasons ]]; then
			for season in $(seq $(($seasons-1)) -1 1)
				do
					echo $(wget -qO- -A.html "$link?season=$season" | sed -n 's/.*title\/tt\([0-9]*\).*/\1/p' | sort | uniq | tr "\n" ",")
					sleep 1
				done 
		fi
	done
