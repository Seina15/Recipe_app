<?php
use Fuel\Core\Controller;
use Fuel\Core\Response;
use Fuel\Core\Input;

// 取得したレシピから、ユーザープロフィールに基づいてフィルタリングを行うAPI

class Controller_Api_Recommend_Recipe extends Controller
{

    public function action_ranking()
    {
        try {
           
            $userIdParam = Input::get("userid", null);

            if ($userIdParam === null){
                $userIdParam = Input::get("userId", null);
            }

            if ($userIdParam === null) {
                return $this->bad_request("userId is required");
            }

            $userId     = (int)$userIdParam;
            $keyword    = trim((string)Input::get("keyword", ""));
            $categoryId = Input::get("categoryId");
            $limitParam = (int)Input::get("limit", 3);
            $limit      = max(1, min(10, $limitParam));

            $profileInfo = \Model_Recommend_Recipe::getProfileInfo($userId);

            if ($categoryId) {
                $res = \Model_Recipe::category_ranking($categoryId);

                if (!$res["success"]){
                    return $this->proxy_error($res);
                }

                $result = [];
                if (isset($res["data"]["result"])) {
                    $result = $res["data"]["result"];
                }
                $categories = [[
                    "categoryId" => $categoryId,
                    "result"     => $result,
                ]];


            } elseif ($keyword !== "") {

                $keywordRes = \Model_Recipe::find_categoryId_by_keyword($keyword, $limit);
                
                if (!$keywordRes["success"]) return $this->proxy_error($keywordRes);
                if (isset($keywordRes["data"])) {
                    $res = $keywordRes["data"];
                } else {
                    $res = [];
                }

                $categories = [];

                if (!empty($res)) {
                    $multiRes = \Model_Recipe::multi_category_ranking($res);

                    if (!$multiRes["success"]) return $this->proxy_error($multiRes);
                    $categories = $multiRes["data"];
                }

            } else {
                $def = \Model_Recipe::default_rankings();

                if (!$def["success"]){
                    return $this->proxy_error($def);
                }
                $categories = $def["data"];
            }


            // フィルタの適用
            foreach ($categories as &$cat) {
                if (isset($cat["result"])) {
                    $lst = $cat["result"];
                } else {
                    $lst = [];
                }
                if (is_array($lst) && !empty($lst)) {
                    $lst = \Model_Recommend_Recipe::FilterRecipeByProfile(
                        $lst,
                        $profileInfo["avoid"],
                        $profileInfo["cook_time"],
                        $profileInfo["budget"]
                    );
                }
                $cat["result"] = $lst;
            }
            unset($cat);


            // フィルタ後のレシピをDBに保存
            foreach ($categories as $cat) {
                $result = $cat["result"] ?? [];
                if (!empty($cat["categoryId"]) && is_array($result) && !empty($result)) {
                    \Model_Recommend_Recipe::upsertRecommendations(
                        $cat["categoryId"],
                        $result
                    );
                }
            }


            // --- レスポンス ---
            return Response::forge(json_encode([
                "success" => true,
                "data"    => [
                    "categories" => $categories,
                ],
            ]), 200)->set_header("Content-Type", "application/json");



        } catch (\Throwable $e) {
            return Response::forge(json_encode([
                "success" => false,
                "stage"   => "exception",
                "error"   => $e->getMessage(),
            ]), 200)->set_header("Content-Type", "application/json");
        }
    }


    //不正リクエスト
    private function bad_request(string $msg)
    {
        return Response::forge(json_encode([
            "success"=>false,
            "stage"  =>"bad_request",
            "error"  =>$msg
        ]), 200)->set_header("Content-Type","application/json");
    }


    //　プロキシエラー
    private function proxy_error(array $res)
    {
        $out = ["success"=>false];
        foreach (["stage","error","http","raw_head"] as $k) {
            if (isset($res[$k])) $out[$k] = $res[$k];
        }
        return Response::forge(json_encode($out), 200)
            ->set_header("Content-Type", "application/json");
    }
}
