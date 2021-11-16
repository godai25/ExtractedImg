<?php
	//-- 定数宣言 
	// アップロード先フォルダ
	define("UPLOAD_FOLDER",     "D:\\work\\php_base64_test");

	// -----------------------------------------------------------------
	//    メイン処理
	//    概要： ファイルを受取 -> BASE64探索 -> BASE64から画像出力 -> ダウンロード
	//
	//    備考: クライアントからはaJaxにて利用する
	//          Windows用です。base64をデコードするcertutilコマンドはファイルであることが必須
	// -----------------------------------------------------------------

	
	$tempfile  = $_FILES['input_file']['tmp_name'];
	$filename  = get_upload_folder();
	$filename .= $_FILES['input_file']['name'];


	//-- アップロード無しは終了
	if (!is_uploaded_file($tempfile)) {
		output_log("ファイルがアップロードされていません");
		exit;
	}

	//-- ファイル移動失敗は終了
	if ( !move_uploaded_file($tempfile , $filename )) {
		output_log("[".$tempfile ."] -> [".$filename."]のファイル移動が完了できません。");
	} 
	
	//-- ファイルをチェックしてBASE64データを画像へ
	if (isset($filename) && file_exists($filename))	{
		$zipfile = generate_img($filename);
		downloader($zipfile);
	}

	//-- 終了
	exit;




	// ==========================================================================================
	// ==========================================================================================

	// -----------------------------------------------------------------
	//    MIMEタイプを考慮したファイルダウンロード
	//
	//    引数1: ファイルパス
	//    引数2: MIMEタイプ（引数ナシは自動判定）
	// -----------------------------------------------------------------
	function downloader($FilePath, $MimeType = null)
	{
	    //-- ファイルが読めない時はエラー
	    if (!is_readable($FilePath)) {
			output_log('$FilePath=['.$FilePath ."]の読み込みができません。");
			die() ;
		}

	    $mimeType = (isset($MimeType)) ? $MimeType : (new finfo(FILEINFO_MIME_TYPE))->file($FilePath);

	    //-- 適切なMIMEタイプが得られない時は、未知のファイルを示すapplication/octet-streamとする
	    if (!preg_match('/\A\S+?\/\S+/', $mimeType)) {
	        $mimeType = 'application/octet-stream';
	    }

	    header('Content-Type: ' . $mimeType);
	    header('X-Content-Type-Options: nosniff');
	    header('Content-Length: ' . filesize($FilePath));
	    header('Content-Disposition: attachment; filename="' . basename($FilePath) . '"');
	    header('Connection: close');
	
	    //-- readfile()の前に出力バッファリングを無効化する
	    while (ob_get_level()) { ob_end_clean(); }
		
	    //-- 出力
	    readfile($FilePath);
		
	    //-- 最後に終了させるのを忘れない
	    exit;
	}

	// -----------------------------------------------------------------
	//    アップロード ファイルからBASE64を判別し画像を生成
	//
	//    引数1 : ファイルパス
	//    戻り値: zipファイルのフルパス
	//    備考  : jpg->BASE64変換すると先頭文字「/9j/4」になる
	//            png->BASE64変換すると先頭文字「iVBORw0KGgoAAAA」になる
	// -----------------------------------------------------------------
	function generate_img($file_path)
	{

		//-- ファイル読み込み候補をまとめる
		$ary_lines = file($file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		$base64_match = array();
		foreach ($ary_lines as $line){
			preg_match('|"/9j/4.*"|', $line, $match);
			if (count($match) >= 1){
				array_push( $base64_match , str_replace('"','',$match[0]));
			}
		}
		
		//-- 候補を確認
		if (count($base64_match) === 0){ return ;}
	
	
		//-- tmpフォルダにbase64データを出力する
		$file_cnt = 1000;
		$base_dir = pathinfo($file_path)['dirname'];

		foreach ($base64_match as $b64){
			$fh = fopen( "${base_dir}\\tmp\\${file_cnt}.txt", "w");
			fwrite($fh, $b64);
			fclose($fh);
			$file_cnt++;
		}

		//-- jpgに変換
		for($i=1000; $i<$file_cnt; $i++){
			// certutil -f -decode 入力ファイル名 出力ファイル名
			$in_file  = "${base_dir}\\tmp\\${i}.txt";
			$out_file = "${base_dir}\\img\\${i}.jpg";
			$cmd = "certutil -f -decode ${in_file} ${out_file}";
			exec($cmd);
		}

		//-- 圧縮する
		$za         = new \ZipArchive();
		$tmpZipPath = "${base_dir}\\image.zip";
		$za->open($tmpZipPath, \ZipArchive::CREATE); 
		for($i=1000; $i<$file_cnt; $i++){
			$file = "${base_dir}\\img\\${i}.jpg";
			$za->addFile($file, "${i}.jpg");
		}
		$za->close();
	
		return $tmpZipPath;
	}

	// -----------------------------------------------------------------
	//    ログ出力
	//
	//    引数1: メッセージ
	//	  備考:  1)日時を記載
	//	         2)./my_php_[yyyyMM].logに出力
	// -----------------------------------------------------------------
	function output_log($msg)
	{
		$date = new DateTime();
		error_log($date->format('Y/m/d H:i:s').' '. $msg."\n", 3, './my_php_'.$date->format('Ym').'.log');
	}

	// -----------------------------------------------------------------
	//    アップロードのフォルダを生成する
	//
	//    引数1: [なし]
	//    戻り値: 生成したアップロード用フォルダ
	//	  備考:  1) フォルダは定数と[年月日]_[時分秒]_[乱数5桁]の合わせ
	//	  備考:  2) base64データを補完するtmpフォルダとimgフォルダも生成
	// -----------------------------------------------------------------
	function get_upload_folder()
	{
		$base_folder = UPLOAD_FOLDER;
		if(!preg_match('/\\$/', UPLOAD_FOLDER)){ $base_folder .=  '\\';} 
		
		$date = new DateTime();
		$make_folder  = $base_folder. $date->format('Ymd_His').'_'. sprintf('%05d', rand(1,99999)).'\\' ;
		mkdir($make_folder, 0777);
		mkdir($make_folder.'\\tmp', 0777);
		mkdir($make_folder.'\\img', 0777);
		return $make_folder;

	}


?>