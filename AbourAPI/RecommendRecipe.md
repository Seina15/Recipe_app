# Recommend Recipe API 仕様書

## エンドポイント

- URL: `/index.php/api/recommend_recipe/ranking.json`
- メソッド: `GET`
- Content-Type: `application/json`

## 概要
ユーザーのプロフィールやキーワード、カテゴリIDなどをもとに、
アプリ内に表示するレシピをフィルタリングするAPI。

## リクエスト例
### カテゴリ指定
```http
GET /index.php/api/recommend_recipe/ranking.json?userId=1&categoryId=31
```

### キーワード指定
```http
GET /index.php/api/recommend_recipe/ranking.json?userId=1&keyword=カレー
```

## パラメータについて
| userId       | int    | 必須 | ユーザーID
| categoryId   | string | 任意 | カテゴリID
| keyword      | string | 任意 | 検索キーワード
| limit        | int    | 任意 | 取得カテゴリ数　指定


## レスポンス例
### 成功
```json
{
  "success": true,
  "data": {
    "_src": "recommend",
    "userId": 1,
    "keyword": "カレー",
    "categories": [
      {
        "categoryId": "31",
        "result": [
          {
            "recipeTitle": "簡単カレー",
            "recipeUrl": "https://...",
            "foodImageUrl": "https://...",
            "recipeMaterial": ["玉ねぎ", "にんじん", "カレー粉"],
            "recipeIndication": "30分",
            "recipeCost": "500円"
          }
        ]
      }
    ],
    "prefs": {
      "avoid": "卵",
      "cook_time": 30,
      "budget": 1000,
      "servings": 2
    },
    "save_stats": [
      { "categoryId": "31", "attempted": 10, "affected": 10 }
    ]
  }
}
```

### 失敗
```json
{
  "success": false,
  "stage": "exception",
  "error": "エラーメッセージ"
}
```

## 備考
- レシピデータは外部API（楽天レシピ等）から取得しています。
