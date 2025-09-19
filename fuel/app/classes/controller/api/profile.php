<?php

class Controller_Api_Profile extends Controller_Rest
{
    protected $format = "json";

    public function post_index()
    {
        try {
            $userId = \Session::get("user_id");
            if (!$userId) {
                return $this->response(["success"=>false,"error"=>"unauthorized"], 401);
            }


            $raw = file_get_contents("php://input"); // 文字列として全文を読み込み

            // 入力データが読み取れなかったとき
            if ($raw === false) {
                return $this->response(["success" => false, "error" => "failed to read input data"], 400);
            }

            $data = json_decode($raw, true);


            // JSONとして解釈できなかったとき
            if ($data === null && trim($raw) !== "null") {
                return $this->response(["success" => false, "error" => "cannot parse JSON"], 400);
            }
 

            if (!is_array($data)) {
                $data = [];
            }

            //　「嫌いなもの・アレルギー食材」の正規化
            $avoid = null;
            if (isset($data["avoid"])) {
                $tmp = trim((string)$data["avoid"]);
                if ($tmp !== "") {
                    // 入力正規化（ひらがな・カタカナ）
                    $tmp = mb_convert_kana($tmp, "cH", "UTF-8");
                    $tmp = mb_convert_kana($tmp, "C", "UTF-8");
                    $tmp = str_replace("　", " ", $tmp);

                    // 漢字対応  by Copilot
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
                    foreach ($ngSynonyms as $kanji => $synonyms) {
                        foreach ($synonyms as $synonym) {
                            if (strpos($tmp, $synonym) !== false) {
                                $tmp = $kanji;
                                break 2;
                            }
                        }
                    }
                    $avoid = $tmp;
                }
            }


            // 「調理時間」の正規化
            $cook_time = null;

            if (isset($data["cook_time"]) && $data["cook_time"] !== "") {
                $val = is_string($data["cook_time"]) ? trim($data["cook_time"]) : $data["cook_time"];
                $tm = filter_var($val, FILTER_VALIDATE_INT);

                if ($tm === false || $tm < 0) {
                    return $this->response(["success" => false, "error" => "cook_time is not int"], 400);
                }
                $cook_time = (int)$tm;
            }


            // 「予算」の正規化
            $budget = null;
            if (isset($data["budget"]) && $data["budget"] !== "") {
                $bdg = filter_var($data["budget"], FILTER_VALIDATE_INT);

                if ($bdg === false || $bdg < 0) {
                    return $this->response(["success" => false, "error" => "budget is not int"], 400);
                }
                $budget = (int)$bdg;
            }



            // --- DBに保存 ---
            $sql = "
                INSERT INTO user_profile (user_id, avoid, cook_time, budget, updated_at)
                VALUES (:userid, :avoid, :cook_time, :budget, NOW())
                ON DUPLICATE KEY UPDATE
                    avoid      = VALUES(avoid),
                    cook_time       = VALUES(cook_time),
                    budget     = VALUES(budget),
                    updated_at = NOW()
            ";

            $params = [
                ":userid"   => $userId,
                ":avoid"    => $avoid,
                ":cook_time"     => $cook_time,
                ":budget"   => $budget,
            ];
            \DB::query($sql)->parameters($params)->execute();


            return $this->response(["success" => true], 200);


            
        } catch (\Throwable $e) {
            \Log::error($e->getMessage()." ".$e->getFile().":".$e->getLine());
            return $this->response(["success" => false, "error" => $e->getMessage()], 500);
        }
    }
}
