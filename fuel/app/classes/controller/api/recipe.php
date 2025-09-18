<?php
use Fuel\Core\Controller;
use Fuel\Core\Response;
use Fuel\Core\Input;

// 楽天レシピからカテゴリランキングを取得し、JSONで返すAPI

class Controller_Api_Recipe extends Controller
{
    public function action_ranking()
    {
        try {
            $keyword    = trim((string)Input::get("keyword", "")); // 検索キーワード(フロント側より指定) 
            $limitParam = (int)Input::get("limit", 2); // 取得上限（負荷によってかえる）
            $categoryId = Input::get("categoryId"); // カテゴリID（APIに登録されているもの）


            if ($categoryId) {

                $res = \Model_Recipe::category_ranking($categoryId);
                if (!$res["success"]) {
                    return $this->error_response($res);
                }

                $SubmitData = [
                    "categories" => [[
                    "categoryId" => $categoryId,
                    "result"     => $res["data"]["result"] ?? []
                    ]]
                ];

            } elseif ($keyword !== "") {
                 $idsRes = \Model_Recipe::find_categoryId_by_keyword($keyword, max(1, $limitParam));

                if (!$idsRes["success"]) {
                    return $this->error_response($idsRes);
                }

                if (isset($idsRes["data"])) {
                    $ids = $idsRes["data"];
                } else {
                    $ids = [];
                }

                if (empty($ids)) {
                    $SubmitData = ["keyword" => $keyword, "categories" => []];

                } else {
                    $multi = \Model_Recipe::multi_category_ranking($ids);

                    if (!$multi["success"]) {
                        return $this->error_response($multi);
                    }

                    $SubmitData = ["keyword" => $keyword, "categories" => $multi["data"]];
                }

            } else {
    
                // キーワードが指定されてない時は、デフォルトのランキングを返す
                $default = \Model_Recipe::default_rankings();
                if (!$default["success"]) {
                    return $this->error_response($default);
                }
                $SubmitData = ["default" => ["31","32","14"], "categories" => $default["data"]];
            }

            
            return Response::forge(json_encode([
                "success" => true,
                "data"    => $SubmitData,
            ]), 200)->set_header("Content-Type", "application/json");


        } catch (\Throwable $e) {
            return Response::forge(json_encode([
                "success" => false,
                "stage"   => "exception",
                "error"   => $e->getMessage(),
            ]), 200)->set_header("Content-Type", "application/json");
        }
    }



    private function error_response(array $res)
    {
        $out = ["success"=>false];
        foreach (["stage","error","http","raw_head"] as $k) {
            if (isset($res[$k])) $out[$k] = $res[$k];
        }

        return Response::forge(json_encode($out), 200)
            ->set_header("Content-Type", "application/json");
    }
}
