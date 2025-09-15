<?php
use Fuel\Core\Controller;
use Fuel\Core\Response;
use Fuel\Core\Input;

class Controller_Api_Recipe extends Controller
{
    public function action_ranking()
    {
        try {
            // ユーザーIDの取得
            $userIdParam = Input::get("userid", null);
            if ($userIdParam === null) {
                $userIdParam = Input::get("userId", null);
            }
            $userId = ($userIdParam !== null) ? (int)$userIdParam : null;


            // 楽天レシピAPI
            $categoryId = Input::get("categoryId");
            $res = \Model_Recipe::category_ranking($categoryId);


            if (!$res["success"]) {
                $SubmitData = ["success"=>false, "stage"=>$res["stage"]];
                if (!empty($res["error"])){
                    $SubmitData["error"] = $res["error"];
                }
                if (!empty($res["http"])){
                    $SubmitData["http"]  = $res["http"];
                }
                if (!empty($res["raw_head"])){
                    $SubmitData["raw_head"] = $res["raw_head"];
                }
                return Response::forge(json_encode($SubmitData), 200)
                    ->set_header("Content-Type", "application/json");
            }

            $data = $res["data"];



            $results = [];
            if (isset($data["result"]) && is_array($data["result"])) {
                $results = $data["result"];
            }


            if ($userId !== null) {
                $prefs = \Model_UserProfile::get_prefs($userId);
                $results = $this->filterRecipesByProfile($results, $prefs["avoid"], $prefs["time"], $prefs["budget"]);
                $data["result"] = $results;
            }

            return Response::forge(json_encode([
                "success" => true,
                "data"    => $data,
            ]), 200)->set_header("Content-Type", "application/json");


        } catch (\Throwable $e) {
            return Response::forge(json_encode([
                "success" => false,
                "stage"   => "exception",
                "error"   => $e->getMessage(),
            ]), 200)->set_header("Content-Type", "application/json");
        }
    }




    private function parseAvoidTokens(?string $s): array
    {
        if ($s === null){
            return [];
        }

        $s = mb_convert_kana(trim($s), "s", "UTF-8");
        if ($s === "") {
            return [];
        }

        $s = strtr($s, [
            "、"=>" ","，"=>" ",","=>" ",
            "／"=>" ","/"=>" ","・"=>" ","|"=>" "
        ]);

        $parts = preg_split("/\s+/", $s, -1, PREG_SPLIT_NO_EMPTY);
        return array_values(array_unique(array_map("trim", $parts)));
    }




    // 調理時間の正規化
    private function indicationToMinutes($indication): ?int
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



    // NG食材が含まれるかの判定用関数
    private function containsAvoid(array $recipe, array $avoidTokens): bool
    {
        if (empty($avoidTokens)) return false;

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

        $materials = $recipe["recipeMaterial"] ?? [];

        $normalizedMaterials = [];
        foreach ($materials as $mat) {
            $matText = mb_convert_kana((string)$mat, "cH", "UTF-8");
            $matText = mb_convert_kana($matText, "C", "UTF-8");
            $matText = str_replace("　", " ", $matText);
            foreach ($ngSynonyms as $kanji => $syns) {
                foreach ($syns as $syn) {
                    if (strpos($matText, $syn) !== false) { $matText = $kanji; break 2; }
                }
            }
            $normalizedMaterials[] = $matText;
        }

        $normalizedTokens = [];
        foreach ($this->parseAvoidTokens(implode(" ", $avoidTokens)) as $tok) { /* 保険 */ }

        foreach ($avoidTokens as $tok) {
            $token = mb_convert_kana((string)$tok, "cH", "UTF-8");
            $token = mb_convert_kana($token, "C", "UTF-8");
            $token = str_replace("　", " ", $token);
            foreach ($ngSynonyms as $kanji => $syns) {
                foreach ($syns as $syn) {
                    if (strpos($token, $syn) !== false) { $token = $kanji; break 2; }
                }
            }
            if ($token !== "") $normalizedTokens[] = $token;
        }

        foreach ($normalizedMaterials as $matText) {
            foreach ($normalizedTokens as $token) {
                if (mb_stripos($matText, $token, 0, "UTF-8") !== false) return true;
            }
        }
        return false;
    }


    
    // プロフィール情報でレシピをフィルタリングする関数
    private function filterRecipesByProfile(
        array $recipes,
        ?string $avoid,
        ?int $time,
        ?int $budgetYen = null
    ): array


    {
    $avoidTokens = $this->parseAvoidTokens($avoid);
    $noAvoid     = empty($avoidTokens);
    $noTime      = ($time === null);
    $budgetRank  = $this->budgetYenToRank($budgetYen);
    $noBudget    = ($budgetRank === null);


    if ($noAvoid && $noTime && $noBudget) {
        return $recipes;
    }

    $out = [];
    foreach ($recipes as $r) {

        if (!$noAvoid && $this->containsAvoid($r, $avoidTokens)) {
            continue;
        }

        if (!$noTime) {
            $mins = $this->indicationToMinutes($r["recipeIndication"] ?? null);
            if ($mins === null || $mins > $time) {
                continue;
            }
        }

        if (!$noBudget) {
            $rr = $this->recipeCostToRank($r["recipeCost"] ?? null);
            if ($rr === null || $rr > $budgetRank) {
                continue;
            }
        }

        $out[] = $r;
    }

    usort($out, function($a, $b) {
        $am = null;
        if (isset($a["recipeIndication"])) {
            $am = $this->indicationToMinutes($a["recipeIndication"]);
        }
        $bm = null;
        if (isset($b["recipeIndication"])) {
            $bm = $this->indicationToMinutes($b["recipeIndication"]);
        }
        $ai = null;
        if ($am === null) {
            $ai = PHP_INT_MAX;
        } else {
            $ai = $am;
        }
        $bi = null;
        if ($bm === null) {
            $bi = PHP_INT_MAX;
        } else {
            $bi = $bm;
        }
        if ($ai === $bi) {
            return 0;
        } else if ($ai < $bi) {
            return -1;
        } else {
            return 1;
        }
    });

    return $out;
}

    
    
    // ユーザーが設定した予算を楽天API用にランク化する関数
    private function budgetYenToRank(?int $yen): ?int
    {
        if ($yen === null) return null;
        if ($yen <= 100)   return 1;
        if ($yen <= 300)   return 2;
        if ($yen <= 500)   return 3;
        if ($yen <= 1000)  return 4;
        if ($yen <= 2000)  return 5;
        if ($yen <= 3000)  return 6;
        if ($yen <= 5000)  return 7;
        return 8;  //それ以上
    }




    // 楽天APIのrecipeCostは文字列なので、アプリに合わせるための変換関数
    private function recipeCostToRank(?string $s): ?int
    {
        if (!$s) return null;
        $k = str_replace([" ", "　", ","], "", $s);

        static $map = [
            "100円以下"   => 1,
            "300円前後"   => 2,
            "500円前後"   => 3,
            "1000円前後"  => 4,
            "2000円前後"  => 5,
            "3000円前後"  => 6,
            "5000円前後"  => 7,
            "10000円以上" => 8,
        ];
        if (isset($map[$k])){
            return $map[$k];
        }

        if (preg_match("/(\d{2,5})円/u", $k, $m)) {
            return $this->budgetYenToRank((int)$m[1]);
        }
        return null;
    }

}
