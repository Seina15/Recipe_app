<?php
use Fuel\Core\Controller_Rest;
use Fuel\Core\Input;
use Fuel\Core\Session;

// ログインに関するAPI

class Controller_Api_Login extends Controller_Rest
{
    protected $format = "json";
    protected $auth_required = false;

    public function before()
    {
        parent::before();
    }

    public function post_index()
    {
        $data = Input::json();
        if (!$data) {
            $data = Input::post();
        }

        if (empty($data["username"]) || empty($data["password"])) {
            return $this->response(
                ["success" => false, "error" => "ユーザー名とパスワードを入力して下さい"],
                400
            );
        }

        $username = trim($data["username"]);

        $user = \Model_User::find_by_username($username);

        if (!$user || !password_verify($data["password"], $user["password_hash"])) {
            return $this->response(
                ["success" => false, "error" => "ユーザー名かパスワードが違います"],
                401
            );
        }

        Session::set("userID", (int)$user["id"]);

        return $this->response([
            "success" => true,
            "user" => [
                "id"       => (int)$user["id"],
                "username" => $user["username"],
            ]
        ], 200);
    }
}
