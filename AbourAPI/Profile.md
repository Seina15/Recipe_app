# Profile API 仕様書

## エンドポイント

- URL: `/index.php/api/profile.json`
- メソッド: `POST`
- Content-Type: `application/json`

## 概要
ユーザーのプロフィール情報（嫌いなもの・アレルギー、調理時間、予算、人数）を登録・更新します。

## リクエスト例
```json
{
  "avoid": "卵, 牛乳",
  "time": 30,
  "budget": 1000,
  "servings": 2
}
```

### パラメータ詳細

avoid      | string  | 任意（NULLでも可） | 嫌いなもの・アレルギー食材
time       | int     | 任意 （NULLでも可）| 調理時間（分）
budget     | int     | 任意 （NULLでも可）| 予算（円）
servings   | int     | 任意 （NULLでも可）| 何人分 

## バリデーション
- `time` 0以上の整数
- `budget` 0以上の整数（100区切り）
- `servings` 1以上の整数

## レスポンス例
### 成功
```json
{
  "success": true
}
```

### 失敗
```json
{
  "success": false,
  "error": "エラーメッセージ"
}
```

## 備考
- 認証未実装のため、ユーザーIDは固定（1）になっています。
- データはDBの `user_profile` テーブルに保存されます。
