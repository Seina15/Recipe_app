<?php

class Controller_Api_Register extends Controller_Rest
{
    protected $format = "json";

  
    public function post_register_form()
    {
        try {
            $raw = file_get_contents("php://input");
            $in  = json_decode($raw, true) ?: [];

            $username = trim((string)($in["username"] ?? ""));
            $password = (string)($in["password"] ?? "");

            if ($username === "" || $password === "") {
                return $this->response(["success" => false, "error" => "username/password required"], 400);
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
            \Log::error($e->getMessage()." ".$e->getFile().":".$e->getLine());
            if (strpos($e->getMessage(), "Duplicate entry") !== false) {
                return $this->response(["success" => false, "error" => "username already exists"], 400);
            }
            return $this->response(["success" => false, "error" => "internal server error"], 500);
        }
    }
}
