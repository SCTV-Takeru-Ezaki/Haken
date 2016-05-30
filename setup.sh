#!/bin/bash

if [ -e /home/pituser/public_html/form ]
then
    exit 0
else
    mkdir /home/pituser/public_html/form
    git clone https://github.com/yusukeasari/Haken.git /home/pituser/public_html/form/

    chmod 0707 /home/pituser/public_html/form/uploads/
    chmod 0707 /home/pituser/public_html/form/log/
    chmod 0666 /home/pituser/public_html/form/log/duplicateList.log
    chmod 0666 /home/pituser/public_html/form/init/init.json
fi


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
