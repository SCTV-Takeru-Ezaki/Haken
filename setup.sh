#!/bin/bash

#ファイルlog/out.logの有無で実行済みか判定
if [ ! -e log/out.log ]
	then
		echo "setup.sh実行済みです。"
	else
		chmod 0707 uploads/ log/
		chmod 0666 log/out.log log/duplicateList.log
		mkdir ~/log
		chmod 0777 ~/log
		mv log/out.log ~/log/
		echo "init/init.jsonのendDateを修正ください。"
fi
