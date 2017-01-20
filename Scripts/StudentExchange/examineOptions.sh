#!/bin/sh

if [ $# -eq 0 ]
then
	echo Missing Arguments
	echo
	echo 1st Argument:
	echo \'Country\' for universities in that country or \'all\' for all countries
	echo
	echo Second argument \(optional\):
	echo - CS for sorted by COMPSci rank
	echo - E for sorted by Engineering rank
	echo 
	exit 1
	 
fi

if [ "$1" = "all"  ] 
then
	egrepCommand="egrep -v ''"
else
	egrepCommand="egrep -i $1"
fi

if [ "$2" = "CS" ]
then
	sortCommand="sort -t, -k3 -n" 
elif [ "$2" = "E" ]
then
	sortCommand="sort -t, -k4 -n"
else
	sortCommand="cat"
fi
$egrepCommand universityRankings.txt | $sortCommand  

