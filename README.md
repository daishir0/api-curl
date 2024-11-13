# api-curl

## Overview
A PHP-based web content fetcher that allows you to fetch content from URLs with XPath extraction and caching capabilities. This tool is useful for scraping web content with proper caching mechanisms and API key authentication.

Key features:
- URL content fetching with caching
- XPath-based content extraction
- API key authentication
- Configurable caching duration
- Detailed error logging
- Security-focused design

## Installation

1. Clone the repository:
```bash
git clone https://github.com/daishir0/api-curl.git
```

2. Navigate to the project directory:
```bash
cd api-curl
```

3. Copy the configuration file and edit it:
```bash
cp config.php.example config.php
```

4. Update the configuration in `config.php`:
- Set your API key
- Configure cache directory
- Adjust timeout settings
- Set debug mode as needed

5. Create cache directory:
```bash
mkdir cache
chmod 755 cache
```

## Usage

Make POST requests to the script with the following parameters:

```bash
curl -X POST \
  -H "API-KEY: your-api-key" \
  -H "URL: https://example.com" \
  -H "XPATH: //div[@class='content']" \
  -H "FORCE: 0" \
  https://your-server/path/to/api-curl.php
```

Parameters:
- `API-KEY`: Your authentication key (required)
- `URL`: Target URL to fetch (required)
- `XPATH`: XPath expression to extract specific content (optional)
- `FORCE`: Set to 1 to bypass cache (optional, default: 0)

Response Headers:
- `X-Cache: HIT` when content is served from cache
- `X-Cache: MISS` when content is freshly fetched

## Notes

- Ensure proper permissions for cache directory
- Configure error logging in production
- Update API key in config.php before deployment
- Check URL allowlist/blocklist if needed
- Monitor cache directory size
- Consider rate limiting for production use

---

# api-curl

## 概要
XPath抽出とキャッシュ機能を備えたPHPベースのWebコンテンツ取得ツールです。適切なキャッシュメカニズムとAPIキー認証を使用してWebコンテンツをスクレイピングするのに役立ちます。

主な機能：
- URLコンテンツの取得とキャッシュ
- XPathベースのコンテンツ抽出
- APIキー認証
- 設定可能なキャッシュ期間
- 詳細なエラーログ
- セキュリティ重視の設計

## インストール方法

1. レポジトリをクローンします：
```bash
git clone https://github.com/daishir0/api-curl.git
```

2. プロジェクトディレクトリに移動：
```bash
cd api-curl
```

3. 設定ファイルをコピーして編集：
```bash
cp config.php.example config.php
```

4. `config.php`の設定を更新：
- APIキーの設定
- キャッシュディレクトリの設定
- タイムアウト設定の調整
- デバッグモードの設定

5. キャッシュディレクトリの作成：
```bash
mkdir cache
chmod 755 cache
```

## 使い方

以下のパラメータでPOSTリクエストを送信します：

```bash
curl -X POST \
  -H "API-KEY: your-api-key" \
  -H "URL: https://example.com" \
  -H "XPATH: //div[@class='content']" \
  -H "FORCE: 0" \
  https://your-server/path/to/api-curl.php
```

パラメータ：
- `API-KEY`: 認証キー（必須）
- `URL`: 取得対象のURL（必須）
- `XPATH`: 特定のコンテンツを抽出するためのXPath式（オプション）
- `FORCE`: キャッシュをバイパスする場合は1（オプション、デフォルト：0）

レスポンスヘッダー：
- `X-Cache: HIT` キャッシュからコンテンツを提供する場合
- `X-Cache: MISS` 新規にコンテンツを取得する場合

## 注意点

- キャッシュディレクトリの権限を適切に設定してください
- 本番環境ではエラーログを適切に設定してください
- デプロイ前にconfig.phpのAPIキーを更新してください
- 必要に応じてURLの許可/ブロックリストを確認してください
- キャッシュディレクトリのサイズを監視してください
- 本番環境ではレート制限の導入を検討してください

## License
This project is licensed under the MIT License - see the LICENSE file for details.

## ライセンス
このプロジェクトはMITライセンスの下でライセンスされています。詳細はLICENSEファイルを参照してください。
