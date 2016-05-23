#!/bin/bash

chmod 0707 uploads/ log/
chmod 0666 log/duplicateList.log	
chmod 0666 init/init.json

# ユーザディレクトリに~log/の有無チェック
if [ ! -e /home/pituser/.htpasswd ]
then
    htpasswd -c -b /home/pituser/.htpasswd form form01
else
    htpasswd -b /home/pituser/.htpasswd form form01
fi

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