  This project aims to build converters from aegisub's .ass subtitles to the decoratable subtitles, like bold, italic and strikethrough, which Youtube receives.<br>
  このプロジェクトでは、aegisub で使われている .ass ファイルを、Youtubeで使える太字や斜体、打ち消し線などの装飾が可能なファイル形式に変換することを目指す。

  Youtube describes about the subtitle types they receive below:<br>
  Youtubeは以下のページでどの字幕形式を受けつけるか公開している。<br>
  https://support.google.com/youtube/answer/2734698

  I, Ryow, have already scratched the first scripts for WebVTT and TTML. See the descriptions below:<br>
  WebVTTとTTMLは作成済み。以下に説明を付す。



<h2>Descriptions</h2>


<h3>Basic descriptions</h3>

* This project has begun in PHP because I only can write PHP code.

* I designed that the scripts receive just one argument; the full path to the target .ass file. They try to read it and write back the new unique file to the direcotry which contains the .ass.

* Usage:<br>
  <code># php /full/path/to/the/target.ass</code><br>
  This command produces the file like this:<br>
  <code>target_1000.vtt</code>

* Only these tags can be used: &lt;b&gt;, &lt;i&gt; and &lt;s&gt;.


<h3>基本的な説明</h3>

* このプロジェクトは、わたし（りょう）がPHPしか使えないため、PHPのみで製作された。

* スクリプトの引数は「対象 .ass ファイルへのフルパス」である。ファイルを読み込んで、ユニークなファイル名で .ass ファイルと同じディレクトリに新しいファイルを書き出す。

* 使い方は以下の通り。<br>
  <code># php /full/path/to/the/target.ass</code><br>
  最終的に以下のようなファイルを作成する。<br>
  <code>target_1000.vtt</code>

* Youtubeの仕様と同じく、&lt;b&gt;, &lt;i&gt; and &lt;s&gt; のみ使用可能。



<h2>Specifications</h2>


<h4>WebVTT (ass2vtt.php)</h4>

* Same tag types as the basic description can be used.

* The file extension is .vtt.


<h4>TTML (ass2ttml.php)</h4>

* Same tag types as the basic description can be used.

* The file extension is .ttml.



<h3>仕様</h3>


<h4>WebVTT (ass2vtt.php)</h4>

* 基本的な説明に書かれたタグのみ使用可能。

* 拡張子は .vtt 。


<h4>TTML (ass2ttml.php)</h4>

* 基本的な説明に書かれたタグのみ使用可能。

* 拡張子は .vtt 。
