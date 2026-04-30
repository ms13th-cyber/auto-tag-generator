=== Local Auto Tag Generator ===
Contributors: masato shibuya(Image-box Co., Ltd.)
Tags: taxonomy, tag, auto tag, japanese, nlp
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 8.0
Stable tag: 1.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

JpnForPhpライブラリ（TinySegmenter）を使用した、ローカル完結型の日本語自動タグ生成プラグインです。

== Description ==

このプラグインは、投稿の保存時（新規作成・更新時）にタイトルと本文を解析し、重要なキーワードを抽出して自動的にタグとして登録します。
外部APIを使用せず、サーバー内のPHPライブラリ（JpnForPhp）で形態素解析を行うため、高速かつプライバシーに配慮した動作が特徴です。

主な機能：
* 日本語の形態素解析によるキーワード抽出
* 既存タグとの一致を優先する重み付けアルゴリズム
* 除外ワード設定による精度のカスタマイズ
* 過去記事への一括タグ生成機能（AJAXによる非同期処理）
* 特定の投稿タイプへの限定適用

== Installation ==

1. `auto-tag-generator` フォルダを `/wp-content/plugins/` ディレクトリにアップロードします。
2. `lib/JpnForPhp/` フォルダ内に JpnForPhp ライブラリが配置されていることを確認してください。
   (構成例: lib/JpnForPhp/Analyzer/TinySegmenter.php)
3. WordPressの「プラグイン」メニューから有効化します。
4. 「設定」 > 「Local Auto Tag」から、対象の投稿タイプを選択し、必要に応じて除外ワードを設定してください。

== Screenshots ==

1. 設定画面：対象投稿タイプの選択と除外ワードの設定、一括生成ボタン。

== Changelog ==

= 1.3 =
* タグの生成ロジック部分を修正。

= 1.2 =
* テキスト修正。

= 1.1 =
* テキスト修正。
* オンライン更新の確認。

= 1.0 =
* 初版リリース。

== Frequently Asked Questions ==

= 外部API（GoogleやYahoo!）への通信は発生しますか？ =
いいえ、すべての解析はサーバー上のPHPのみで完結します。

= 解析の精度を上げるには？ =
「設定」画面の除外ワードに、タグとしてふさわしくない助詞や頻出単語を追加することで、相対的に重要なキーワードが残りやすくなります。