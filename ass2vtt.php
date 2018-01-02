<?php

/*
 * .ASS 2 VTT
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
WEBVTT

%s
EOL;
$timecodes	= "";
$timecode_line	= '%s0 --> %s0' . PHP_EOL . '%s' . PHP_EOL . PHP_EOL;
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
	$timecode_matches	= array();
	$tag_matches	= array();
	if (preg_match('/(\d+:\d+:\d+\.\d+),(\d+:\d+:\d+\.\d+)/', $ass_line, $timecode_matches)) {

		// タイムコードを整形する
		foreach (array($timecode_matches[1], $timecode_matches[2]) as $index => $timecode) {
			$exploded	= explode(':', $timecode, 2);
			$timecode_matches[$index+1]	= sprintf("%02d:", $exploded[0]) . $exploded[1];
		}
		$begin	= $timecode_matches[1];
		$end	= $timecode_matches[2];

		// スクリプトを取得する
		$exploded	= explode(',', $ass_line, 10);
		$text	= rtrim(array_pop($exploded));

		// ASSタグをWEBVTTに変換する

		// 0 ~ 1 と 1 ~ 0 の2パターンを処理する
		$tag_match_result	= preg_match_all("/\\{\\\\(\w+)0\\}(.*?)\\{\\\\\\1[1]\\}/", $text, $tag_matches, PREG_SET_ORDER);
		if ($tag_match_result !== false && $tag_match_result >= 1) {
			foreach ($tag_matches as $tag_match) {
				$text	= str_replace($tag_match[0], $tag_match[2], $text);
			}
		}

		$tag_match_result	= preg_match_all("/(\\{\\\\(\w+)1\\})(.*?)(\\{\\\\\\2[0]\\})/", $text, $tag_matches, PREG_SET_ORDER);
		if ($tag_match_result !== false && $tag_match_result >= 1) {
			foreach ($tag_matches as $tag_match) {
				$start_pos	= strpos($text, $tag_match[1]);
				$end_pos	= strrpos($text, $tag_match[4]);
				$tag_width	= mb_strwidth($tag_match[1], "UTF-8");
				$start_tag	= "<" . $tag_match[2] . ">";
				$end_tag	= "</" . $tag_match[2] . ">";

				// 文字列の位置を狂わせないようにするために、後ろのタグから処理する
				$text	= substr_replace($text, $end_tag, $end_pos, $tag_width);
				$text	= substr_replace($text, $start_tag, $start_pos, $tag_width);
			}
		}

		// 改行
		$text	= str_replace("\\N", PHP_EOL, $text);

		$timecodes	.= sprintf($timecode_line, $begin, $end, $text);
	}
}

// タイムコードデータが取れていなければ注意
if (empty($timecodes)) {
	trigger_error("No timecodes are contained in your .ass file.", E_USER_NOTICE);
}

// ファイル生成
$template	= sprintf($template, $timecodes);

// 元デのあった場所でファイル名がユニークになるように、数字をいい感じに付加する
$realfile	= "";
for ($i = 1; true; $i++) {

	$new_filename	= preg_replace('/\.ass$/', '_' . sprintf("%04d", $i) . '.vtt', $ass_filename);
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