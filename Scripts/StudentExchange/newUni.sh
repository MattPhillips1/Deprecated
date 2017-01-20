#!/bin/sh

if [ $# -eq 0 ]
then
	echo usage:
	echo \"Name of University\" \"Country\" \"COMPSci rank\" \"Engineering Rank\"
	echo
	exit 1
fi

if [ "$3" = "nil" ]
then
	compRank="9999 (nil)"
else
	compRank="$3"
fi

if [ "$4" = "nil" ]
then
	engRank="9999 (nil)"
else
	engRank="$4"
fi
echo "$1, $2, $compRank COMPSci, $engRank Engineering" >> universityRankings.txt
