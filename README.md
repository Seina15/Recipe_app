# クックリスト
<img width="800" height="448" alt="image" src="https://github.com/user-attachments/assets/38fc1634-475d-4528-a3ed-096446dee826" />

## アプリ概要

あなたやご家族の好み、人数、予算に合わせて、最適なレシピを提案するアプリです。

* 選択したレシピの材料を**自動でお買い物リストに追加**
* **手動追加**にも対応：他の買い物もアプリひとつで完結
<img width="1275" height="788" alt="image" src="https://github.com/user-attachments/assets/2f292806-75a9-4985-a1a2-cfdb2ee64e49" />

---

## 作成の背景

一人暮らしをしている中で、

* 毎回レシピを検索する
* 買い物リストをメモに書く

といった作業を繰り返していた。
これを **一括で管理できる仕組み** が欲しいと考え、このアプリを作成しました。

---

## 使用技術

* **言語**: PHP 7.3
* **フレームワーク**: FuelPHP 1.8
* **インフラ**: Docker
* **ライブラリ**: Knockout.js
* **データベース**: MySQL
 
### デザイン関連
- **フォント**:  
  - [M PLUS Rounded 1c](https://fonts.google.com/specimen/M+PLUS+Rounded+1c)  
  - [Kosugi Maru](https://fonts.google.com/specimen/Kosugi+Maru)

- **アイコン**:  
  - [Font Awesome 6 Free](https://fontawesome.com/icons)  
---

## 📂 構成

* レシピ検索 & レコメンド
* ショッピングリスト管理

  * レシピから自動追加
  * 手動での追加・削除

---

## 今後の展望

* レシピAPIとの連携（住んでいる地域の天気・人数に応じた最適提案）
* ログイン機能の追加（ユーザーごとの好みを保存）
* UIの改善


## 現在確認されている不具合

* 内部の処理が重く、プロフィール情報が反映されない時があります。


# 立ち上げ方

※.envに楽天レシピAPIのアプリIDを設定する必要があります。

## 1. コンテナを起動する

```bash
cd docker
docker compose up -d
```

## 2. データベースを作成する

queriesファイル内のSQLファイルを実行してテーブルを作成します。

## 3. アプリにアクセスする

ブラウザで以下にアクセスしてください:
[http://localhost:8080](http://localhost:8080)