<?php

use Fuel\Core\Controller_Rest;

// プロフィール情報を保存するAPI

class Controller_Api_Profile extends Controller_Rest
{
    protected $format = "json";

    public function post_index()
    {
        try {
            $userId = \Session::get("user_id");
            if (!$userId) {
                return $this->response(["success"=>false,"error"=>"unauthorized-error"], 401);
            }

            
            $raw = file_get_contents("php://input");
            if ($raw === false) {
                return $this->response(["success"=>false,"error"=>"failed to read input data"], 400);
            }

            $data = json_decode($raw, true);
            if ($data === null && trim($raw) !== "null") {
                return $this->response(["success"=>false,"error"=>"cannot parse JSON"], 400);
            }

            if (!is_array($data)){
                $data = [];
            }


            $norm = \Model_UserProfile::normalize($data);
            \Model_UserProfile::upsert((int)$userId, $norm["avoid"], $norm["cook_time"], $norm["budget"]);

            return $this->response(["success"=>true], 200);

        } catch (\InvalidArgumentException $e) {
            return $this->response(["success"=>false,"error"=>$e->getMessage()], 400);

        } catch (\Throwable $e) {
            \Log::error($e->getMessage()." ".$e->getFile().":".$e->getLine());
            return $this->response(["success"=>false,"error"=>"internal error"], 500);
        }
    }
}
