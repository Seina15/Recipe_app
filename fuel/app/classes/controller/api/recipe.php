<?php
use Fuel\Core\Controller;
use Fuel\Core\Response;

class Controller_Api_Recipe extends Controller

// Response::forge($body, $status)
{
    public function action_index()
        {   
            return Response::forge(
                json_encode(["success"=>true,"where"=>"recipe/index"]), 200
            )->set_header("Content-Type","application/json");
        }

    public function action_ranking(){

        // 正常時の処理
        try {
            $app_id = getenv("RAKUTEN_APP_ID");
            if (!$app_id)
                throw new \RuntimeException("RAKUTEN_APP_IDが設定されていません。");


            // カテゴリーIDの取得
            $categoryId = \Fuel\Core\Input::get('categoryId');
            $url = "https://app.rakuten.co.jp/services/api/Recipe/CategoryRanking/20170426"
                . "?applicationId=" . rawurlencode($app_id)
                . ($categoryId ? "&categoryId=" . rawurlencode($categoryId) : "");

            // cURLの初期化
            $ch = curl_init($url);
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>10]);
            $raw  = curl_exec($ch); // 戻り値
            $http = curl_getinfo($ch, CURLINFO_RESPONSE_CODE); // HTTPステータスコード
            $error_msg  = curl_error($ch);  // エラーメッセージ
            curl_close($ch);


            // success:成功判定, stage:段階, error:エラーメッセージ, http:HTTPステータスコード, raw_head:レスポンス
            if ($raw === false) {
                return \Response::forge(json_encode(["success"=>false,"stage"=>"curl","error"=>$error_msg]),200)
                    ->set_header("Content-Type","application/json");
            }


            if ($http >= 400) {
                return \Response::forge(json_encode(["success"=>false,"stage"=>"upstream","http"=>$http,"raw_head"=>mb_substr($raw,0,200)]),200)
                    ->set_header("Content-Type","application/json");
            }


            $data = json_decode($raw,true);
            if (!is_array($data)) {
                return \Response::forge(json_encode(["success"=>false,"stage"=>"parse","error"=>"non-json","raw_head"=>mb_substr($raw,0,200)]),200)
                    ->set_header("Content-Type","application/json");
            }

            return \Response::forge(json_encode(["success"=>true,"data"=>$data]),200)
                ->set_header("Content-Type","application/json");


        // エラー
        } catch (\Throwable $e) {
            return \Response::forge(json_encode(["success"=>false,"stage"=>"exception","error"=>$e->getMessage()]),200)
                ->set_header("Content-Type","application/json");
        }
    }
}
