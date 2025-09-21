<?php

// ユーザー登録用のAPI

class Controller_Api_Register extends Controller_Rest
{
    protected $format = "json";

    // ユーザー登録フォームの送信する関数
    public function post_register_form()
    {
        try {
            $raw = file_get_contents("php://input");
            $in = json_decode($raw, true);
            if (!$in) {
                $in = [];
            }
                
            $username = "";
            if (isset($in["username"])) {
                $username = $in["username"];
            }
            $username = trim((string)$username);


            $password = "";
            if (isset($in["password"])) {
                $password = $in["password"];
            }
            $password = (string)$password;


            if ($username === "" || $password === "") {
                return $this->response(["success" => false, "error" => "usernameとpasswordは必須です"], 400);
            }


            $hash = password_hash($password, PASSWORD_DEFAULT);

            list($id, $rows) = \DB::insert("users")->set([
                "username"   => $username,
                "password"   => $hash,
                "created_at" => \DB::expr("NOW()"),
                "updated_at" => \DB::expr("NOW()"),
            ])->execute();


            \Session::set("user_id", (int)$id);
            return $this->response(["success" => true, "id" => (int)$id], 201);


        } catch (\Throwable $e) {
            if (strpos($e->getMessage(), "Duplicate entry") !== false) {
                return $this->response(["success" => false, "error" => "username already exists"], 400);
            }

            return $this->response(["success" => false, "error" => "internal server error"], 500);
        }
    }
}
