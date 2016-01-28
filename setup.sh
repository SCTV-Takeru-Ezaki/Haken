#!/bin/bash

chmod 0707 uploads/ log/
chmod 0666 log/duplicateList.log	

# ユーザディレクトリに~log/の有無チェック
if [ ! -e ~/log ]
then
	mkdir ~/log
	chmod 0777 ~/log
	touch ~/log/out.log
	chmod 0666 ~/log/out.log
	echo "~/log/out.logを作成しました。"
else
	rm -rf log/out.log
	echo "~/logディレクトリはあります。"
fi
echo "init/init.jsonのendDateを確認ください。"