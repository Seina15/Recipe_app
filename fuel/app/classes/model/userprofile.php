<?php
use Fuel\Core\DB;

// ユーザープロフィールの管理

class Model_UserProfile extends \Model
{

    // プロフィール情報の正規化を行う関数
    public static function normalize(array $data): array
    {

        // Avoidの正規化
        $avoid = null;
        if (isset($data["avoid"])) {

            $tmp = trim((string)$data["avoid"]);
            if ($tmp !== "") {
                $tmp = mb_convert_kana($tmp, "cHC", "UTF-8");
                $tmp = str_replace("　", " ", $tmp);
                $ngSynonyms = [
                    "鶏肉" => ["鶏肉","チキン","とり肉"],
                    "豚肉" => ["豚肉","ポーク","ぶた肉"],
                    "牛肉" => ["牛肉","ビーフ","ぎゅう肉"],
                    "卵"   => ["卵","たまご","タマゴ"],
                    "乳製品" => ["乳製品","牛乳","チーズ","ヨーグルト","バター","ミルク"],
                    "小麦" => ["小麦","パン","パスタ","うどん","ラーメン","そうめん","蕎麦"],
                    "魚介類" => ["魚介類","魚","エビ","カニ","イカ","タコ","貝"],
                    "茄子" => ["茄子","ナス"],
                    "胡瓜" => ["胡瓜","キュウリ"],
                    "南瓜" => ["南瓜","カボチャ"],
                    "葱"   => ["葱","ネギ"],
                ];

                foreach ($ngSynonyms as $kanji => $syns) {
                    foreach ($syns as $s) {
                        if (strpos($tmp, $s) !== false) { $tmp = $kanji; break 2; }
                    }
                }
                $avoid = $tmp;
            }
        }


        // Cook_time（調理時間）の正規化
        $cook_time = null;
        if (isset($data["cook_time"]) && $data["cook_time"] !== "") {

            $t = filter_var($data["cook_time"], FILTER_VALIDATE_INT, ["options"=>["min_range"=>0]]);
            if ($t === false) {
                throw new \InvalidArgumentException("cook_time is not int");
            }
            $cook_time = (int)$t;
        }


        // Budget（予算）の正規化
        $budget = null;
        if (isset($data["budget"]) && $data["budget"] !== "") {
            $b = filter_var($data["budget"], FILTER_VALIDATE_INT, ["options"=>["min_range"=>0]]);
            if ($b === false) {
                throw new \InvalidArgumentException("budget is not int");
            }
            $budget = (int)$b;
        }

        return ["avoid"=>$avoid, "cook_time"=>$cook_time, "budget"=>$budget];
    }


    // プロフィール情報の保存する関数
    public static function upsert(int $userId, ?string $avoid, ?int $cook_time, ?int $budget): void
    {
        $sql = "
            INSERT INTO user_profile (user_id, avoid, cook_time, budget, updated_at)
            VALUES (:user_id, :avoid, :cook_time, :budget, NOW())
            ON DUPLICATE KEY UPDATE
                avoid = VALUES(avoid),
                cook_time = VALUES(cook_time),
                budget = VALUES(budget),
                updated_at = NOW()
        ";

        DB::query($sql)->parameters([
            ":user_id"   => $userId,
            ":avoid"     => $avoid,
            ":cook_time" => $cook_time,
            ":budget"    => $budget,
        ])->execute();
    }


    // プロフィール情報の取得する関数
    public static function get_profile(int $userId): array
    {
        $sql = "SELECT avoid, cook_time, budget FROM user_profile WHERE user_id = :uid LIMIT 1";
        $res = DB::query($sql)->parameters([":uid"=>$userId])->execute();
        $row = $res->current();
        if (!$row) {
            $row = [];
        }

        $avoid = null;
        if (isset($row["avoid"])) {
            $tmp = trim((string)$row["avoid"]);
            if ($tmp !== "") {
                $avoid = $tmp;
            }
        }

        $cook_time = null;
        if (isset($row["cook_time"]) && $row["cook_time"] !== null) {
            $cook_time = (int)$row["cook_time"];
        }

        $budget = null;
        if (isset($row["budget"]) && $row["budget"] !== null) {
            $budget = (int)$row["budget"];
        }

        return [
            "avoid" => $avoid,
            "cook_time" => $cook_time,
            "budget" => $budget,
        ];
    }
}
