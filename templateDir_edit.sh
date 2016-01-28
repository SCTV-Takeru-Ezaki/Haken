#!/bin/bash

#titleタグ修正
echo "titleタグの用のキャンペーン名を入力してください。"
read CAMPAIGN_NAME
find ./templateDir -type f -exec perl -p -i -e "s/<title>.*<\/title>/<title>$CAMPAIGN_NAME<\/title>/" {} \;
echo "titleタグの差し替え完了"

#GoogleAnalyticsトラッキングID差し替え
#基本的に1度のみ実行「// ga('create',・・・)」とコメントアウトされていないと差し替えない
while :
do
	echo "GoogleAnalyticsトラッキングIDを入力してください。"
	read GA_ID
	GA_ID_PRESTR=`echo $GA_ID | cut -c 1-3`

	# 入力値の先頭3文字分のみ正しいかチェック
	# todo 正規表現を利用したほうが正確
	if [ $GA_ID_PRESTR = "UA-" ]
	then
		find ./templateDir -type f -exec perl -p -i -e "s/\/\/ ga\('create', 'UA-.*', 'auto'\);/ga\('create', '$GA_ID', 'auto'\);/" {} \;
		break
	else
		echo "トラッキングIDが'UA*'ではありません。'UA-XXXXXXXX-XX'で入力ください。"	
	fi
done

echo "GAのダッシュボード上でユーザ属性取得を有効化してください。"
