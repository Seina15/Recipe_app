<?php
use Fuel\Core\Controller_Rest;
use Fuel\Core\Input;
use Fuel\Core\Session;

// ログインに関するAPI

class Controller_Api_Login extends Controller_Rest
{
    protected $format = "json";
    protected $auth_required = false;

    // ログイン処理用関数
    public function post_index()
    {
        $data = Input::json();
        if (!$data) {
            $data = Input::post();
        }

        if (empty($data["username"]) || empty($data["password"])) {
            return $this->response(
                ["success" => false, "error" => "ユーザー名とパスワードを入力してください"],
                400
            );
        }

        $username = trim($data["username"]);
        $user = \Model_User::find_by_username($username);

        if (!$user || !password_verify($data["password"], $user["password_hash"])) {
            return $this->response(
                ["success" => false, "error" => "ユーザー名またはパスワードが違います"],
                401
            );
        }

        \Session::destroy();
        \Session::create();
        \Session::set("user_id", (int)$user["id"]);
        $secret = bin2hex(random_bytes(32));
        \Session::set("login_secret", $secret);

        return $this->response([
            "success" => true,
            "user" => [
                "id"       => (int)$user["id"],
                "username" => $user["username"],
            ],
            "login_secret" => $secret,
        ], 200);
    }
}

// 参考元：https://qiita.com/redrabbit1104/items/a3eaf2bba51fac0b3c51
