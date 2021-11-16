# ExtractedImg
web service. Extracte from HAR File(HTTP Archive File ) include BASE64 string to jpeg file.

ブラウザのF12ボタンや、レスポンス解析ツールから生成できるHAR(HTTP Archive File)からjpegファイルを生成します。

# 特徴
* harファイルに複数含まれているBASE64テキストの内容を一括で画像ファイル（jpgに限る）を生成し、zipにしてダウンロード

# 使い方
* test01.htmlを開き、harファイルを選択して、実行を押してください。

# 備考
* Windows用です。
* 任意に、アップロードのフォルダ（プログラム上では定数[UPLOAD_FOLDER]）を指定しなおしてください。
