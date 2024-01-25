#!/bin/bash

SHELL=/bin/sh
PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin

cd /home/web/contabil.work/storage/system/temp/certbot


for file in *; 

do

 . ./$file

 done