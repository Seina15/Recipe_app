<?php

// 楽天APIからレシピ情報を取得し、DBに保存 or 更新する

class Model_Recipe extends \Model
{

    // 楽天APIから「カテゴリランキング」を取得する関数
    public static function category_ranking(?string $categoryId = null): array

    {
        $app_id = getenv("RAKUTEN_APP_ID");
        if (!$app_id) {
            return [
                "success" => false,
                "stage"   => "config",
                "error"   => "RAKUTEN_APP_ID is not set in .env"
            ];
        }

        $url = "https://app.rakuten.co.jp/services/api/Recipe/CategoryRanking/20170426"
             . "?applicationId=" . rawurlencode($app_id);

        if ($categoryId) {
            $url .= "&categoryId=" . rawurlencode($categoryId);
        }

        // cURLでAPIリクエスト
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
        ]);
        $raw      = curl_exec($ch);
        $http     = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $errorMsg = curl_error($ch);
        curl_close($ch);

        // 通信エラー
        if ($raw === false) {
            return [
                "success" => false,
                "stage"   => "curl",
                "error"   => $errorMsg
            ];
        }
        // HTTPエラー
        if ($http >= 400) {
            return [
                "success"  => false,
                "stage"    => "upstream",
                "http"     => $http,
                "raw_head" => mb_substr($raw, 0, 200)
            ];
        }

        // JSONを配列に変換
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return [
                "success"  => false,
                "stage"    => "parse",
                "error"    => "non-json",
                "raw_head" => mb_substr($raw, 0, 200)
            ];
        }

        return [
            "success" => true,
            "data"    => $data
        ];
    }


    // 楽天APIから「カテゴリ一覧」を取得する関数
    public static function category_list(): array
    {
        $app_id = getenv("RAKUTEN_APP_ID");
        if (!$app_id) {
            return [
                "success" => false,
                "stage"   => "config",
                "error"   => "RAKUTEN_APP_ID is not set in .env"
            ];
        }

        $url = "https://app.rakuten.co.jp/services/api/Recipe/CategoryList/20170426"
             . "?applicationId=" . rawurlencode($app_id);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
        ]);

        $raw      = curl_exec($ch);
        $http     = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $errorMsg = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            return [
                "success" => false,
                "stage"   => "curl",
                "error"   => $errorMsg
            ];
        }
        if ($http >= 400) {
            return [
                "success"  => false,
                "stage"    => "upstream",
                "http"     => $http,
                "raw_head" => mb_substr($raw, 0, 200)
            ];
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return [
                "success"  => false,
                "stage"    => "parse",
                "error"    => "non-json",
                "raw_head" => mb_substr($raw, 0, 200)
            ];
        }

        return [
            "success" => true,
            "data"    => $data
        ];
    }


    // キーワードから「カテゴリID」を検索するための関数
    public static function find_categoryId_by_keyword(string $keyword, int $limit = 5): array
    {
        // カテゴリ一覧の取得
        $categotyList = self::category_list();
        if (!$categotyList["success"]) return $categotyList;

        // キーワードの正規化
        $normalizedKeyword = mb_convert_kana(trim($keyword), "KVas", "UTF-8");
        if ($normalizedKeyword === "") return ["success"=>true, "data"=>[]];

        // カテゴリ一覧からキーワードを含むカテゴリIDを検索（魚、肉等のカテゴリ名）
        if (isset($categotyList["data"]["result"])) {
            $result = $categotyList["data"]["result"];
        } else {
            $result = [];
        }

        $ids = [];

        //　以下キーワードから始まるカテゴリIDを探す
        // （Largeカテゴリ、Mediumカテゴリ、Smallカテゴリの順で探索）

        // Largeカテゴリ
        foreach (($result["large"] ?? []) as $cat) {
            if (mb_stripos($cat["categoryName"], $normalizedKeyword, 0, "UTF-8") !== false) {
                $ids[] = (string)$cat["categoryId"];
            }
        }

        // Mediumカテゴリ
        foreach (($result["medium"] ?? []) as $cat) {
            if (mb_stripos($cat["categoryName"], $normalizedKeyword, 0, "UTF-8") !== false) {
                $ids[] = $cat["parentCategoryId"] . "-" . $cat["categoryId"];
            }
        }

        // Smallカテゴリ
        $parentOfMedium = [];
        foreach (($result["medium"] ?? []) as $m) {
            $parentOfMedium[$m["categoryId"]] = $m["parentCategoryId"];
        }
        foreach (($result["small"] ?? []) as $cat) {
            if (mb_stripos($cat["categoryName"], $normalizedKeyword, 0, "UTF-8") !== false) {
                $mediumId = $cat["parentCategoryId"];
                $largeId  = $parentOfMedium[$mediumId] ?? null;
                if ($largeId) {
                    $ids[] = $largeId . "-" . $mediumId . "-" . $cat["categoryId"];
                }
            }
        }

        // 重複除去と上限の設定
        $ids = array_values(array_unique($ids));
        if ($limit > 0) {
            $ids = array_slice($ids, 0, $limit);
        }

        return [
            "success" => true,
            "data"    => $ids
        ];
    }



    // 各カテゴリIDの「レシピランキング」を取得する関数
    public static function multi_category_ranking(array $categoryIds): array
    {
        $out = [];
        foreach ($categoryIds as $cid) {
            $rankingData = self::category_ranking($cid);

            if ($rankingData["success"]) {
                $out[] = [
                    "categoryId" => $cid,
                    "result"     => isset($rankingData["data"]["result"]) ? $rankingData["data"]["result"] : []
                ];

            } else {
                $out[] = [
                    "categoryId" => $cid,
                    "error"      => $rankingData
                ];
            }

            // APIの付加防止に１待ち
            usleep(1000000); 
        }

        return [
            "success" => true,
            "data"    => $out
        ];
    }



    //　デフォルトのカテゴリを設定（キーワードが設定されてない時に表示する）関数
    public static function default_rankings(): array
    {
        // large: 肉=31, 魚=32, ご飯もの=14
        $defaultCategoryIds = ["31", "32", "14"];
        return self::multi_category_ranking($defaultCategoryIds);
    }



    // APIから取得した調理時間を「分」に変換する関数（by ChatGPT）
    public static function upsert_recommendations(string $categoryId, array $recipes): void
    {
        foreach ($recipes as $recipe) {
            
            $indicationMin = self::indicationToMinutes($recipe["recipeIndication"] ?? null);
            \DB::query(
                "INSERT INTO recommend_recipe
                   (category_id, recipe_id, title, recipe_url, image_url, indication_min, recipe_cost)
                 VALUES
                   (:cid, :rid, :title, :url, :img, :imin, :rc)
                 ON DUPLICATE KEY UPDATE
                   title=VALUES(title), recipe_url=VALUES(recipe_url), image_url=VALUES(image_url),
                   indication_min=VALUES(indication_min), recipe_cost=VALUES(recipe_cost)"

            )->parameters([
                "cid"   => $categoryId,
                "rid"   => (string)($recipe["recipeId"] ?? ""),
                "title" => (string)($recipe["recipeTitle"] ?? ""),
                "url"   => (string)($recipe["recipeUrl"] ?? ""),
                "img"   => (string)($recipe["mediumImageUrl"] ?? $recipe["foodImageUrl"] ?? ""),
                "imin"  => $indicationMin,
                "rc"    => (string)($recipe["recipeCost"] ?? ""),
            ])->execute();
        }
    }
}
