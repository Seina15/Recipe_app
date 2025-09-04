<?php
use Fuel\Core\Controller;
use Fuel\Core\Response;

class Controller_Api_Recipe extends Controller
{

    // テスト用
    // public function action_index()
    // {
    //     return Response::forge(
    //         json_encode(["success" => true, "where" => "recipe/index"]), 200
    //     )->set_header("Content-Type", "application/json");
    // }


    public function action_ranking()
    {
        try {

            // ユーザーIDの取得
            $userIdParam = \Fuel\Core\Input::get("userid", null);
            if ($userIdParam === null) {
                $userIdParam = \Fuel\Core\Input::get("userId", null);
            }
            $userId = ($userIdParam !== null) ? (int)$userIdParam : null;


            // .envからアプリIDの取得
            $app_id = getenv("RAKUTEN_APP_ID");
            if (!$app_id) {
                throw new \RuntimeException("RAKUTEN_APP_ID is not set in .env");
            }


            // 楽天レシピAPIの呼び出し
            $categoryId = \Fuel\Core\Input::get("categoryId");
            $url = "https://app.rakuten.co.jp/services/api/Recipe/CategoryRanking/20170426"
                . "?applicationId=" . rawurlencode($app_id)
                . ($categoryId ? "&categoryId=" . rawurlencode($categoryId) : "");


            // ☆
            $ch = curl_init($url);
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);// 文字列で返す & 10秒でタイムアウトする。
            $raw  = curl_exec($ch); // リクエストを送信してレスポンスを受け取る
            $http = curl_getinfo($ch, CURLINFO_RESPONSE_CODE); // HTTPステータスコードをもらう
            $error_msg = curl_error($ch); // エラーメッセージをもらう
            curl_close($ch);


            // エラーの確認
            if ($raw === false) {
                return \Response::forge(json_encode([
                    "success" => false, "stage" => "curl", "error" => $error_msg
                ]), 200)->set_header("Content-Type", "application/json");
            }

            if ($http >= 400) {
                return \Response::forge(json_encode([
                    "success" => false, "stage" => "upstream", "http" => $http,
                    "raw_head" => mb_substr($raw, 0, 200)
                ]), 200)->set_header("Content-Type", "application/json");
            }

            $data = json_decode($raw, true);
            if (!is_array($data)) {
                return \Response::forge(json_encode([
                    "success" => false, "stage" => "parse", "error" => "non-json",
                    "raw_head" => mb_substr($raw, 0, 200)
                ]), 200)->set_header("Content-Type", "application/json");
            }



            // 一覧取得
            $results = [];
            if (isset($data["result"]) && is_array($data["result"])) {
                $results = $data["result"];
            }


            // プロフィール情報を元に情報をフィルタリング
            if ($userId !== null) {
                list($avoid, $time) = $this->fetchProfileInfo($userId);
                $results = $this->filterRecipesByProfile($results, $avoid, $time);
                $data["result"] = $results; // ← フィルタ済みを戻す
            }

            // 成功レスポンスをさくせい
            return \Response::forge(json_encode([
                "success" => true,
                "data"    => $data
            ]), 200)->set_header("Content-Type", "application/json");


        // エラーのレスポンス
        } catch (\Throwable $e) {
            return \Response::forge(json_encode([
                "success" => false,
                "stage"   => "exception",
                "error"   => $e->getMessage()
            ]), 200)->set_header("Content-Type", "application/json");
        }
    }


    // プロフィール情報取得
    private function fetchProfileInfo(int $userId): array
    {
        $sql = "SELECT avoid, time FROM user_profile WHERE user_id = :uid LIMIT 1";
        $result = \DB::query($sql)
            ->parameters([":uid" => $userId])
            ->execute();


        $row = $result->current();
        $avoid = null;
        $time  = null;

        if (is_array($row)) {

            if (array_key_exists("avoid", $row)) {
                $tmp = (string)$row["avoid"];
                $tmp = trim($tmp);
                if ($tmp !== "") {
                    $avoid = $tmp;
                }
            }

            if (array_key_exists("time", $row)) {
                $val = $row["time"];

                if ($val !== "" && $val !== null) {
                    $t = filter_var($val, FILTER_VALIDATE_INT, ["options" => ["min_range" => 0]]);
                    if ($t !== false) {
                        $time = (int)$t;
                    }
                }
            }
        }

        return [$avoid, $time];
    }


    // 嫌いなもの・アレルギー食材のトークン化(各単語で区切る)
    private function parseAvoidTokens(?string $s): array
    {
        if ($s === null) return [];
        $s = mb_convert_kana(trim($s), "s", "UTF-8"); 
        if ($s === "") return [];
        $s = strtr($s, [
            "、" => " ", "，" => " ", "," => " ",
            "／" => " ", "/" => " ", "・" => " ", "|" => " "
        ]);
        $parts = preg_split("/\s+/", $s, -1, PREG_SPLIT_NO_EMPTY);
        return array_values(array_unique(array_map("trim", $parts)));
    }


    // 「調理時間」の正規化（分単位）
    private function indicationToMinutes($indication): ?int
    {
        if ($indication === null) {
            return null;
        }

        $txt = trim((string)$indication);
        if ($txt === "") {
            return null;
        }
        $h = 0;
        $m = 0;

        if (preg_match("/(\d+)\s*時間/u", $txt, $mm)) {
            $h = (int)$mm[1];
        }

        if (preg_match("/(\d+)\s*分/u", $txt, $mm)) {
            $m = (int)$mm[1];
        }

        if ($h === 0 && $m === 0) {
            if (preg_match("/(\d+)/u", $txt, $mm)) {
                $m = (int)$mm[1];
            }
        }

        $total = $h * 60 + $m;
        if ($total > 0) {
            return $total;
        } else {
            return null;
        }
    }


    // レシピに嫌いなものが含まれているかの判定
    private function containsAvoid(array $recipe, array $avoidTokens): bool
    {
        if (empty($avoidTokens)) {
            return false;
        }

        $ngSynonyms = [
            "鶏肉" => ["鶏肉", "チキン", "とり肉"],
            "豚肉" => ["豚肉", "ポーク", "ぶた肉"],
            "牛肉" => ["牛肉", "ビーフ", "ぎゅう肉"],
            "卵"   => ["卵", "たまご", "タマゴ"],
            "乳製品" => ["乳製品", "牛乳", "チーズ", "ヨーグルト", "バター", "ミルク"],
            "小麦" => ["小麦", "パン", "パスタ", "うどん", "ラーメン", "そうめん", "蕎麦"],
            "魚介類" => ["魚介類", "魚", "エビ", "カニ", "イカ", "タコ", "貝"],
            "茄子" => ["茄子", "ナス"],
            "胡瓜" => ["胡瓜", "キュウリ"],
            "南瓜" => ["南瓜", "カボチャ"],
            "葱"   => ["葱", "ネギ"],
        ];

        $materials = $recipe["recipeMaterial"] ?? [];

        // 材料名を同義語で代表漢字に変換
        $normalizedMaterials = [];
        foreach ($materials as $mat) {
            $matText = mb_convert_kana((string)$mat, "cH", "UTF-8");
            $matText = mb_convert_kana($matText, "C", "UTF-8");
            $matText = str_replace("　", " ", $matText);
            foreach ($ngSynonyms as $kanji => $synonyms) {
                foreach ($synonyms as $synonym) {
                    if (strpos($matText, $synonym) !== false) {
                        $matText = $kanji;
                        break 2;
                    }
                }
            }
            $normalizedMaterials[] = $matText;
        }


        $normalizedTokens = [];
        foreach ($avoidTokens as $tok) {
            $token = mb_convert_kana((string)$tok, "cH", "UTF-8");
            $token = mb_convert_kana($token, "C", "UTF-8");
            $token = str_replace("　", " ", $token);
            foreach ($ngSynonyms as $kanji => $synonyms) {
                foreach ($synonyms as $synonym) {
                    if (strpos($token, $synonym) !== false) {
                        $token = $kanji;
                        break 2;
                    }
                }
            }
            if ($token !== "") {
                $normalizedTokens[] = $token;
            }
        }

        foreach ($normalizedMaterials as $matText) {
            foreach ($normalizedTokens as $token) {
                if (mb_stripos($matText, $token, 0, "UTF-8") !== false) {
                    return true;
                }
            }
        }

        return false;
    }

        

    // プロフィール情報を元にレシピをフィルタリング
    private function filterRecipesByProfile(array $recipes, ?string $avoid, ?int $time): array
    {
       
        $avoidTokens = $this->parseAvoidTokens($avoid);

        
        $noAvoid = empty($avoidTokens); 
        $noTime  = ($time === null); 

        
        if ($noAvoid && $noTime) {
            return $recipes;
        }

        $out = [];
        foreach ($recipes as $r) {
            if (!$noAvoid) {
                $hasAvoid = $this->containsAvoid($r, $avoidTokens);
                if ($hasAvoid) {
                    continue;
                }
            }

            if (!$noTime) {
                $mins = $this->indicationToMinutes($r["recipeIndication"] ?? null);

                if ($mins === null || $mins > $time) {
                    continue;
                }
            }

            
            $out[] = $r;
        }

      
        usort($out, function ($a, $b) {
            $am = $this->indicationToMinutes($a["recipeIndication"] ?? null);
            $bm = $this->indicationToMinutes($b["recipeIndication"] ?? null);
            $ai = ($am === null) ? PHP_INT_MAX : $am;
            $bi = ($bm === null) ? PHP_INT_MAX : $bm;

            if ($ai === $bi) {
                return 0;
            }
            return ($ai < $bi) ? -1 : 1;
        });

        return $out;
    }
    
}
