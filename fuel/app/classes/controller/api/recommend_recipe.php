<?php
use Fuel\Core\Controller;
use Fuel\Core\Response;
use Fuel\Core\Input;

// 取得したレシピから、ユーザープロフィールに基づいてフィルタリングを行うAPI

class Controller_Api_Recommend_Recipe extends Controller
{

    // ユーザープロフィールに基づいてフィルタリングを行う関数
    public function action_ranking()
    {
        try {
           
            $userIdParam = Input::get("user_id", null);
            $profileName = Input::get("profile_name", null);
            
            if ($userIdParam === null) {
                return $this->bad_request("user_id is required");
            }

            $userId = (int)$userIdParam;
            if (is_null($profileName)) {
                $profileName = "";
            
            } else {
                $profileName = trim((string)$profileName);
            }
            
            $keyword = trim((string)Input::get("keyword", ""));
            $categoryId = Input::get("categoryId");
            $limitParam = (int)Input::get("limit", 3); // 取得上限（負荷によってかえる）
            $limit = max(1, min(10, $limitParam));

            // プロフィール指定があれば取得、なければ空でフィルタなし
            $profileInfo = [
                "avoid" => null,
                "cook_time" => null,
                "budget" => null
            ];
            
            if ($profileName !== "") {
                $profileInfo = \Model_UserProfile::get_profile($userId, $profileName);
            }


            if ($categoryId) {
                $response = \Model_Recipe::category_ranking($categoryId);

                if (!$response["success"]){
                    return $this->proxy_error($response);
                }

                $result = [];
                if (isset($response["data"]["result"])) {
                    $result = $response["data"]["result"];
                }
                $categories = [[
                    "categoryId" => $categoryId,
                    "result"     => $result,
                ]];


            // キーワードが指定されている場合にそれに基づいたカテゴリIDを取得
            } elseif ($keyword !== "") {

                $keywordRes = \Model_Recipe::find_categoryId_by_keyword($keyword, $limit);
                
                if (!$keywordRes["success"]){
                    return $this->proxy_error($keywordRes);
                }

                if (isset($keywordRes["data"])) {
                    $res = $keywordRes["data"];
                } else {
                    $res = [];
                }

                $categories = [];

                // カテゴリIDが見つかった場合に、そのカテゴリIDに基づいたランキングを取得
                if (!empty($res)) {
                    $multiRes = \Model_Recipe::multi_category_ranking($res);

                    if (!$multiRes["success"]) return $this->proxy_error($multiRes);
                    $categories = $multiRes["data"];
                }


            //　デフォルト表示
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
                    $lst = \Model_Recommend_Recipe::filtered_recipe_by_profile(
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
                if (isset($cat["result"])) {
                    $result = $cat["result"];
                } else {
                    $result = [];
                }
                if (!empty($cat["categoryId"]) && is_array($result) && !empty($result)) {
                    \Model_Recommend_Recipe::upsert_recommendation(
                        $cat["categoryId"],
                        $result
                    );
                }
            }

        
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
