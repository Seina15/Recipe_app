<?php
declare(strict_types=1);

use Fuel\Core\DB;

// ユーザープロフィールの管理

class Model_UserProfile extends \Model
{

    // 入力データの正規化用関数
    public static function normalize(array $data): array
    {

        // Profile Name
        if (!isset($data["profile_name"])) {
            throw new \InvalidArgumentException("profile_name is required");
        }

        $name = (string)$data["profile_name"];
        $name = trim($name);
        $name = mb_convert_kana($name, "cHKV", "UTF-8");
        $name = str_replace("　", " ", $name);
        $name = preg_replace("/\s+/", " ", $name);

        if ($name === "" || mb_strlen($name, "UTF-8") > 100) {
            throw new \InvalidArgumentException("profile_name invalid");
        }


        // Avoid （NG食材)
        $avoid = null;
        if (array_key_exists("avoid", $data)) {
            $tmp = trim((string)$data["avoid"]);
            if ($tmp !== "") {
                $tmp = mb_convert_kana($tmp, "cHC", "UTF-8");
                $tmp = str_replace("　", " ", $tmp);

                
                $ngSynonyms = [
                    "鶏肉"   => ["鶏肉","チキン","とり肉"],
                    "豚肉"   => ["豚肉","ポーク","ぶた肉"],
                    "牛肉"   => ["牛肉","ビーフ","ぎゅう肉"],
                    "卵"     => ["卵","たまご","タマゴ"],
                    "乳製品" => ["乳製品","牛乳","チーズ","ヨーグルト","バター","ミルク"],
                    "小麦"   => ["小麦","パン","パスタ","うどん","ラーメン","そうめん","蕎麦"],
                    "魚介類" => ["魚介類","魚","エビ","カニ","イカ","タコ","貝"],
                    "茄子"   => ["茄子","ナス"],
                    "胡瓜"   => ["胡瓜","キュウリ"],
                    "南瓜"   => ["南瓜","カボチャ"],
                    "葱"     => ["葱","ネギ"],
                    "大蒜"   => ["大蒜","ニンニク"],
                    "生姜"   => ["生姜","ショウガ"],
                    "蕎麦"   => ["蕎麦","そば"],
                    "海藻"   => ["海藻","わかめ","のり","昆布"],
                    "豆腐"   => ["豆腐","とうふ"],
                    "大豆製品" => ["大豆製品","納豆","油揚げ","厚揚げ","豆乳"],
                    "芋類"   => ["芋類","じゃがいも","さつまいも","里芋","サツマイモ","ジャガイモ","里芋"],
                    "果物" => ["果物","フルーツ","りんご","バナナ","みかん","オレンジ","ぶどう","グレープ","桃","もも","パイナップル","キウイ","メロン","スイカ","マンゴー","イチゴ","いちご","レモン","ライム","チェリー","さくらんぼ","ブルーベリー","ラズベリー","クランベリー","アボカド"]
                ];
                foreach ($ngSynonyms as $kanji => $syns) {
                    foreach ($syns as $s) {
                        if (mb_strpos($tmp, $s) !== false) { $tmp = $kanji; break 2; }
                    }
                }
                $avoid = $tmp;
            }
        }

        // Cook Time
        $cook_time = null;
        if (isset($data["cook_time"]) && $data["cook_time"] !== "") {
            $t = filter_var($data["cook_time"], FILTER_VALIDATE_INT, ["options" => ["min_range" => 0]]);
            if ($t === false) {
                throw new \InvalidArgumentException("cook_time is not int");
            }
            $cook_time = (int)$t;
        }

        // Budget
        $budget = null;
        if (isset($data["budget"]) && $data["budget"] !== "") {
            $b = filter_var($data["budget"], FILTER_VALIDATE_INT, ["options" => ["min_range" => 0]]);
            if ($b === false) {
                throw new \InvalidArgumentException("budget is not int");
            }
            $budget = (int)$b;
        }

        return [
            "profile_name" => $name,
            "avoid"        => $avoid,
            "cook_time"    => $cook_time,
            "budget"       => $budget,
        ];
    }

    

    // プロフィール追加・更新用関数
    public static function upsert(int $userId, string $profileName, ?string $avoid, ?int $cook_time, ?int $budget): void
    {
        $sql = "
            INSERT INTO user_profile (user_id, profile_name, avoid, cook_time, budget, updated_at)
            VALUES (:user_id, :profile_name, :avoid, :cook_time, :budget, NOW())
            ON DUPLICATE KEY UPDATE
                avoid = VALUES(avoid),
                cook_time = VALUES(cook_time),
                budget = VALUES(budget),
                updated_at = NOW()
        ";

        DB::query($sql)->parameters([
            ":user_id"      => $userId,
            ":profile_name" => $profileName,
            ":avoid"        => $avoid,
            ":cook_time"    => $cook_time,
            ":budget"       => $budget,
        ])->execute();
    }

    

    // プロフィール取得用関数
    public static function get_profile(int $userId, string $profileName): array
    {
        $sql = "SELECT avoid, cook_time, budget
                  FROM user_profile
                 WHERE user_id = :uid
                   AND profile_name = :pname
                 LIMIT 1";

        $res = DB::query($sql)->parameters([
            ":uid"   => $userId,
            ":pname" => $profileName,
        ])->execute();

        $row = $res->current();
        if (!$row) {
            return [
                "avoid"      => null,
                "cook_time"  => null,
                "budget"     => null,
            ];
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
            "avoid"      => $avoid,
            "cook_time"  => $cook_time,
            "budget"     => $budget,
        ];
    }

    

    // プロフィール一覧取得用関数
    public static function list_profiles(int $userId): array
    {
        $sql = "SELECT id, profile_name, updated_at
                  FROM user_profile
                 WHERE user_id = :uid
                 ORDER BY id ASC";

        $res = DB::query($sql)->parameters([":uid" => $userId])->execute();

        $out = [];
        foreach ($res as $row) {
            $out[] = [
                "id"           => (int)$row["id"],
                "profile_name" => (string)$row["profile_name"],
                "updated_at"   => (string)$row["updated_at"],
            ];
        }
        return $out;
    }

    

    // プロフィール名変更用関数
    public static function rename_profile(int $userId, string $oldName, string $newName): void
    {
        $newName = trim(mb_convert_kana(str_replace("　", " ", $newName), "cHKV", "UTF-8"));
        $newName = preg_replace("/\s+/", " ", $newName);
        if ($newName === "" || mb_strlen($newName, "UTF-8") > 100) {
            throw new \InvalidArgumentException("new profile_name invalid");
        }

        $sql = "UPDATE user_profile
                   SET profile_name = :new
                 WHERE user_id = :uid
                   AND profile_name = :old
                 LIMIT 1";

        $res = DB::query($sql)->parameters([
            ":new" => $newName,
            ":uid" => $userId,
            ":old" => $oldName,
        ])->execute();
    }

    

    // プロフィール削除用関数
    public static function delete_profile(int $userId, string $profileName): void
    {
        $sql = "DELETE FROM user_profile
                 WHERE user_id = :uid
                   AND profile_name = :pname
                 LIMIT 1";
                 
        DB::query($sql)->parameters([
            ":uid"   => $userId,
            ":pname" => $profileName,
        ])->execute();
    }
}
