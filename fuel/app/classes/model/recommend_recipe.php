<?php

use Fuel\Core\DB;

class Model_Recommend_Recipe extends \Model
{

    // ユーザープロフィールを取得するする関数
    public static function getProfileInfo(int $userId): array
    {
        $row = DB::query(
            "SELECT avoid, cook_time, budget
               FROM user_profile
              WHERE user_id = :uid
              ORDER BY updated_at DESC, id DESC
              LIMIT 1"
        )->parameters(["uid" => $userId])->execute()->current();

        if (!$row) {
            return ["avoid"=>null, "cook_time"=>null, "budget"=>null];
        }
        return [
            "avoid"    => $row["avoid"],
            "cook_time"=> ($row["cook_time"] !== null ? (int)$row["cook_time"] : null),
            "budget"   => ($row["budget"]   !== null ? (int)$row["budget"]   : null),
        ];
    }


    // ユーザープロフィールから、レシピをフィルタリングする関数
    public static function FilterRecipeByProfile(
        array $recipes,
        ?string $avoid,
        ?int $cook_time,
        ?int $budgetYen = null
    ): array
    {
        $avoidFood = self::normalizedAvoid($avoid);
        $noAvoid  = empty($avoidFood);
        $noTime   = ($cook_time === null);
        $rankMax  = self::yenToRank($budgetYen);
        $noBudget = ($rankMax === null);


        if ($noAvoid && $noTime && $noBudget) {
            usort($recipes, function($a, $b) {
                return self::cmpIndicationMinutesAsc($a, $b);
            });
            return $recipes;
        }

        $out = [];
        foreach ($recipes as $r) {
            if (!$noAvoid && self::containsAvoid($r, $avoidFood)) {
                continue;
            }
            if (!$noTime) {
                $mins = self::indicationToMinutes($r["recipeIndication"] ?? null);
                if ($mins === null || $mins > $cook_time){
                    continue;
                }
            }
            if (!$noBudget) {
                $rr = self::stringToRank($r["recipeCost"] ?? null);
                if ($rr === null || $rr > $rankMax){
                    continue;
                }
            }
            $out[] = $r;
        }

        usort($out, function($a, $b) {
            return self::cmpIndicationMinutesAsc($a, $b);
        });
        return $out;
    }



    // プロフィールの「Avoid（避けたい食材）」を正規化する関数
    private static function normalizedAvoid(?string $s): array
    {
        if ($s === null) return [];
        $s = mb_convert_kana(trim($s), "s", "UTF-8");
        if ($s === "") return [];
        $s = strtr($s, ["、"=>" ","，"=>" ",","=>" ","／"=>" ","/"=>" ","・"=>" ","|"=>" "]);
        $parts = preg_split("/\s+/", $s, -1, PREG_SPLIT_NO_EMPTY);
        return array_values(array_unique(array_map("trim", $parts)));
    }


    // レシピに避けたい食材が含まれているかどうかの判定関数
    private static function containsAvoid(array $recipe, array $avoidFood): bool
    {
        if (empty($avoidFood)) return false;

        $ngSynonyms = [
            "鶏肉"=>["鶏肉","チキン","とり肉"],
            "豚肉"=>["豚肉","ポーク","ぶた肉"],
            "牛肉"=>["牛肉","ビーフ","ぎゅう肉"],
            "卵"  =>["卵","たまご","タマゴ"],
            "乳製品"=>["乳製品","牛乳","チーズ","ヨーグルト","バター","ミルク"],
            "小麦"=>["小麦","パン","パスタ","うどん","ラーメン","そうめん","蕎麦"],
            "魚介類"=>["魚介類","魚","エビ","カニ","イカ","タコ","貝"],
            "茄子"=>["茄子","ナス"],
            "胡瓜"=>["胡瓜","キュウリ"],
            "南瓜"=>["南瓜","カボチャ"],
            "葱"  =>["葱","ネギ"],
        ];

        if (isset($recipe["recipeMaterial"])) {
            $materials = $recipe["recipeMaterial"];

        } else {
            $materials = [];
        }

        $normalizedMaterials = [];

        foreach ($materials as $mat) {
            $matText = mb_convert_kana((string)$mat, "cH", "UTF-8");
            $matText = mb_convert_kana($matText, "C",  "UTF-8");
            $matText = str_replace("　", " ", $matText);

            foreach ($ngSynonyms as $kanji => $syns) {
                foreach ($syns as $syn) {
                    $pos = strpos($matText, $syn);
                    if ($pos !== false) {
                        $matText = $kanji;
                        break 2;
                    }
                }
            }
            $normalizedMaterials[] = $matText;
        }

        $normalizedTokens = [];
        foreach ($avoidFood as $tok) {

            $normalized = mb_convert_kana((string)$tok, "cH", "UTF-8");
            $normalized = mb_convert_kana($normalized, "C",  "UTF-8");
            $normalized = str_replace("　", " ", $normalized);

            foreach ($ngSynonyms as $kanji => $syns) {
                foreach ($syns as $syn) {
                    if (strpos($normalized, $syn) !== false) { $normalized = $kanji; break 2; }
                }
            }
            if ($normalized !== ""){
                $normalizedTokens[] = $normalized;
            }
        }

        foreach ($normalizedMaterials as $matText) {
            foreach ($normalizedTokens as $token) {
                $pos = mb_stripos($matText, $token, 0, "UTF-8");
                if ($pos !== false) {
                    return true;
                }
            }
        }
        return false;
    }



    // 調理時間の文字列を分に変換する関数
    private static function indicationToMinutes($indication): ?int
    {
        if ($indication === null) return null;
        $txt = trim((string)$indication);
        if ($txt === "") return null;
        $h = 0; $m = 0;
        if (preg_match("/(\d+)\s*時間/u", $txt, $mm)) $h = (int)$mm[1];
        if (preg_match("/(\d+)\s*分/u",   $txt, $mm)) $m = (int)$mm[1];
        if ($h === 0 && $m === 0 && preg_match("/(\d+)/u", $txt, $mm)) $m = (int)$mm[1];
        $total = $h * 60 + $m;
        return $total > 0 ? $total : null;
    }

    

    // 予算（円）を扱いやすいようにランク化する関数　[ユーザーのプロフィール情報]
    private static function yenToRank(?int $yen): ?int
        {
            if ($yen === null) return null;

            $ranges = [
                100   => 1,
                300   => 2,
                500   => 3,
                1000  => 4,
                2000  => 5,
                3000  => 6,
                5000  => 7,
            ];
            foreach ($ranges as $max => $rank) {
                if ($yen <= $max) {
                    return $rank;
                }
            }
            return 8;
        }



    // レシピの価格（文字列）を扱いやすいようにランク化する関数　[楽天レシピAPIから得た価格]
    private static function stringToRank(?string $s): ?int
    {
        if (!$s) return null;
        $k = str_replace([" ", "　", ","], "", $s);
        static $map = [
            "100円以下"=>1, "300円前後"=>2, "500円前後"=>3, "1000円前後"=>4,
            "2000円前後"=>5, "3000円前後"=>6, "5000円前後"=>7, "10000円以上"=>8,
        ];
        if (isset($map[$k])) return $map[$k];
        if (preg_match("/(\d{2,5})円/u", $k, $m)) return self::yenToRank((int)$m[1]);
        return null;
    }



    // 調理時間の短い順にソートするための比較関数
    private static function cmpIndicationMinutesAsc($a, $b): int
    {

        // レシピA
        $aMinutes = null;
        if (isset($a["recipeIndication"])) {
            $aMinutes = self::indicationToMinutes($a["recipeIndication"]);
        }
        if ($aMinutes === null) {
            $aValue = PHP_INT_MAX;
        } else {
            $aValue = $aMinutes;
        }

        // レシピB
        $bMinutes = null;
        if (isset($b["recipeIndication"])) {
            $bMinutes = self::indicationToMinutes($b["recipeIndication"]);
        }
        if ($bMinutes === null) {
            $bValue = PHP_INT_MAX;
        } else {
            $bValue = $bMinutes;
        }

        // 比較
        if ($aValue < $bValue) {
            return -1;
        } elseif ($aValue > $bValue) {
            return 1;
        } else {
            return 0;
        }
    }



    // フィルタリング後のレシピをDBに保存する関数
    public static function upsertRecommendations(string $categoryId, array $recipes): int
    {
        if (empty($recipes)) {
            \Log::debug("レシピがありません ID:" . $categoryId);
            return 0;
        }

        $rows   = [];
        $params = [];
        $i = 0;


        foreach ($recipes as $recipe) {
            // レシピID
            $recipeId = "";
            if (isset($recipe["recipeId"])) {
                $recipeId = (string)$recipe["recipeId"];
            }
            if ($recipeId === "") {
                continue;
            }

            // タイトル
            $title = "";
            if (isset($recipe["recipeTitle"])) {
                $title = mb_substr((string)$recipe["recipeTitle"], 0, 255);
            }

            // URL
            $url = "";
            if (isset($recipe["recipeUrl"])) {
                $url = mb_substr((string)$recipe["recipeUrl"], 0, 520);
            }
            if ($title === "" || $url === "") continue;

            // 画像URL
            $img = "";
            if (isset($recipe["mediumImageUrl"])) {
                $img = mb_substr((string)$recipe["mediumImageUrl"], 0, 520);
            } elseif (isset($recipe["foodImageUrl"])) {
                $img = mb_substr((string)$recipe["foodImageUrl"], 0, 520);
            }
            if ($img === "") {
                $img = null;
            }

            // 調理時間（分）
            $imin = null;
            if (isset($recipe["recipeIndication"])) {
                $imin = self::indicationToMinutes($recipe["recipeIndication"]);
            }

            // 価格ランク
            $rc = null;
            if (isset($recipe["recipeCost"])) {
                $rc = self::stringToRank((string)$recipe["recipeCost"]);
            }

            $rows[] = "(:cid{$i}, :rid{$i}, :title{$i}, :url{$i}, :img{$i}, :imin{$i}, :rc{$i})";
            $params["cid{$i}"]   = $categoryId;
            $params["rid{$i}"]   = $recipeId;
            $params["title{$i}"] = $title;
            $params["url{$i}"]   = $url;
            $params["img{$i}"]   = $img;
            $params["imin{$i}"]  = $imin; 
            $params["rc{$i}"]    = $rc;
            $i++;
        }

       
        if (empty($rows)) {
            \Log::debug("レシピがありません ID:" . $categoryId);
            return 0;
        }

    
        $sql = "
        INSERT INTO recommend_recipe
            (category_id, recipe_id, title, recipe_url, image_url, indication_min, recipe_cost)
        VALUES
            ".implode(",\n        ", $rows)."
        ON DUPLICATE KEY UPDATE
            title          = VALUES(title),
            recipe_url     = VALUES(recipe_url),
            image_url      = VALUES(image_url),
            indication_min = VALUES(indication_min),
            recipe_cost    = VALUES(recipe_cost),
            fetched_at     = CURRENT_TIMESTAMP
        ";

        try {
            $res = \DB::query($sql)->parameters($params)->execute();
            $affected = 0;
            if (is_object($res) && method_exists($res, "count")) {
                $affected = $res->count();
            } else {
                $affected = 0;
            }
            return (int)$affected;

        } catch (\Throwable $e) {
            \Log::error("エラーが発生しました: " . $categoryId . " error:" . $e->getMessage());
            return 0;
        }
    }

}