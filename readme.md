# Local Auto Tag Generator

A privacy-focused, "local-first" Japanese auto-tagging plugin. Unlike other plugins that rely on Google or Yahoo APIs, this tool performs morphological analysis directly on your server using the TinySegmenter library.

[日本語の解説は英語の後にあります]

---

## Key Features

- **On-Server Morphological Analysis**: Utilizes a built-in TinySegmenter (via JpnForPhp) to analyze Japanese text. No data is sent to external servers, ensuring 100% privacy and zero API costs.
- **Intelligent Tag Scoring**: Not just simple extraction—it prioritizes existing tags in your database by adding a score weight (+50), ensuring consistency in your taxonomy.
- **Smart Filtering**: Automatically filters out symbols, numbers, and short Hiragana particles (e.g., 2-character particles) to generate high-quality, relevant tags.
- **Bulk Processing via Ajax**: Includes a robust bulk-generation tool that processes your entire post history step-by-step using Ajax to prevent server timeouts.
- **Highly Customizable**: Define which post types to target and manage a global "Exclude Words" list directly from the settings page.

## Installation

1. Upload the `auto-tag-generator` folder to your `/wp-content/plugins/` directory.
2. Ensure the `lib/JpnForPhp/` directory is intact.
3. Activate the plugin through the 'Plugins' menu in WordPress.
4. Go to **Settings > Local Auto Tag** to configure your target post types and exclude words.

---

## 主な機能（日本語）

外部APIに一切依存せず、サーバー内だけで完結する日本語自動タグ生成プラグインです。

- **サーバー内形態素解析**: TinySegmenter（JpnForPhp）を内蔵し、PHPのみで日本語を解析します。データが外部サーバーに送信されないため、プライバシー保護とコストゼロを実現します。
- **インテリジェントなスコアリング**: 単語の出現回数だけでなく、既存のタグと一致した場合はスコアを大幅に加算（+50）する仕組みを搭載。サイト内の既存タクソノミーを尊重します。
- **高度な単語フィルタリング**: 記号、数字、ひらがな2文字の助詞などを自動的に除外し、検索や分類に役立つ「意味のあるキーワード」のみを抽出します。
- **Ajaxによる一括適用**: 過去の記事すべてにタグを生成する一括処理機能を搭載。Ajaxを利用して1記事ずつ処理するため、共用サーバーでもタイムアウトせずに実行可能です。
- **柔軟なカスタマイズ**: 対象とする投稿タイプの選択や、特定の単語をタグから除外するブラックリスト管理を管理画面から行えます。

## インストール

1. `auto-tag-generator` フォルダを `/wp-content/plugins/` にアップロードします。
2. 管理画面の「プラグイン」から有効化してください。
3. 「設定」 > 「Local Auto Tag」から、対象の投稿タイプと除外ワードを設定できます。