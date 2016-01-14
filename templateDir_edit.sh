#!/bin/bash

#titleタグ修正
echo "titleタグの用のキャンペーン名を入力してください。"
read CAMPAIGN_NAME
find ./templateDir -type f -exec perl -p -i -e "s/<title>.*<\/title>/<title>$CAMPAIGN_NAME<\/title>/" {} \;
echo "titleタグの差し替え完了"

#GoogleAnalyticsトラッキングID差し替え
echo "GoogleAnalyticsトラッキングIDを入力してください。"
read GA_ID
find ./templateDir -type f -exec perl -p -i -e "s/\/\/ ga\('create', 'UA-.*', 'auto'\);/ga\('create', '$GA_ID', 'auto'\);/" {} \;
echo "GAのダッシュボード上でユーザ属性取得を有効化してください。"
