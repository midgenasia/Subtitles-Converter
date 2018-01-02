<?php

/*
 * .ASS 2 TTML
 *
 * Argument: Full path to the target .ass file.
 *
 */

// 引数がなければエラー
if (!is_array($argv) || empty($argv)) {
	trigger_error("Specify the full path to your target .ass file as an argument.", E_USER_ERROR);
	exit(1);
}

// Windows環境ではパスの文字コードをCP932にする必要がある
$os	= getenv("OS");
$arg_path	= $argv[1];
if (preg_match('/^windows/i', $os)) {
	mb_internal_encoding("CP932");
	$arg_path	= mb_convert_encoding($arg_path, "CP932", "UTF-8");
}

// 引数が有効なパスかを確認する
if (!is_readable($arg_path)) {
	trigger_error("Check if the path is correct and either the file is readable.", E_USER_ERROR);
	exit(1);
}

// いちおう拡張子もチェックする
$ass_path_array	= explode('\\', $arg_path);
$ass_filename	= array_pop($ass_path_array);
if (!preg_match('/\.ass$/i', $ass_filename)) {
	trigger_error("You specified the file which is not .ass. You specified: $ass_filename", E_USER_ERROR);
	exit(1);
}

// .assがあるディレクトリは保存にも使うので、そのディレクトリが書き込み可能か調べる
$ass_path	= dirname($arg_path);
if (!is_writable($ass_path)) {
	trigger_error("Set the directory containing your target .ass file writable.", E_USER_ERROR);
	exit(1);
}


// 変数の準備
$template	=<<<EOL
<?xml version='1.0' encoding='UTF-8'?>
<tt xmlns='http://www.w3.org/ns/ttml' xml:lang='ja' >
<body>
<div>
%s
</div>
</body>
</tt>
EOL;
$timecodes	= "";
$timecode_line	= '<p begin="%s" end="%s">%s</p>' . PHP_EOL;
$timecodes_start_flag	= false;

// .assを読み込む
$ass_array	= file($arg_path);

// タイムコード情報を取得する
foreach ($ass_array as $ass_line) {

	// タイムコード行が始まるまでスキップ
	if ($timecodes_start_flag === false) {
		if (rtrim($ass_line) === "[Events]") {
			$timecodes_start_flag	= true;
		}
		continue;
	}

	// タイムコードREGEXPにマッチする行のみ処理する
	// 以下、ASSの列の定義
	// Format: Layer, Start, End, Style, Name, MarginL, MarginR, MarginV, Effect, Text
	$matches	= array();
	if (preg_match('/(\d+:\d+:\d+\.\d+),(\d+:\d+:\d+\.\d+)/', $ass_line, $matches)) {

		// タイムコード
		$begin	= $matches[1];
		$end	= $matches[2];

		// スクリプト
		$exploded	= explode(',', $ass_line, 10);
		$text	= rtrim(array_pop($exploded));

		// ASSタグをXMLタグに変換する
		// *ただし b, s, <br/> のみ

		// italic
		$text	= str_replace('{\\i1}', '<span tts:fontStyle="italic">', $text);
		$text	= str_replace('{\\i0}', '</span>', $text);

		// bold
		$text	= str_replace('{\\b1}', '<span tts:fontWeight="bold">', $text);
		$text	= str_replace('{\\b0}', '</span>', $text);

		// 改行
		$text	= str_replace("\\N", '<br/>', $text);

		$timecodes	.= sprintf($timecode_line, $begin, $end, $text);
	}
}

// タイムコードデータが取れていなければ注意
if (empty($timecodes)) {
	trigger_error("No timecodes are contained in your .ass file.", E_USER_NOTICE);
}

// XML生成
$template	= sprintf($template, $timecodes);

// 元デのあった場所でファイル名がユニークになるように、数字をいい感じに付加する
$realfile	= "";
for ($i = 1; true; $i++) {

	$new_filename	= preg_replace('/\.ass$/', '_' . sprintf("%04d", $i) . '.ttml', $ass_filename);
	$realfile	= $ass_path . DIRECTORY_SEPARATOR . $new_filename;

	// ファイル名がユニークなら保存を試みる
	if (!file_exists($realfile)) {
		$result	= file_put_contents($realfile, $template);

		if ($result === false) {
			// 書き込めなかったらエラー
			trigger_error("Failed to write your new file.: {$realfile}", E_USER_ERROR);
			exit(1);
		}

		break;
	}

	// 1000回試してダメならエラー
	if ($i >= 1000) {
		trigger_error("Remove extra '<FILENAME>_<NNNN>.ass' files in the directory containing the processing .ass file.", E_USER_ERROR);
		exit(1);
	}
}

// ここまで来れたら成功。おしまい。
echo "Successfully produced: {$realfile}";
exit(0);

?>