<?php
use Fuel\Core\Controller_Rest;
use Fuel\Core\Input;
use Fuel\Core\DB;
use Fuel\Core\Session;

class Controller_Api_Login extends Controller_Rest
{
    protected $format = "json";

    public function before()
    {
        parent::before();
    }

    public function post_login()
    {
        $data = Input::json();
        if (!$data) {
            $data = Input::post();
        }


        if (empty($data['username']) || empty($data['password'])) {
            return $this->response(
                ["success" => false, "error" => "usernameとpasswordを入力して下さい"],
                400
            );
        }

        $username = trim($data['username']);
        $user = DB::query(
            "SELECT id, username, password AS password_hash, name
             FROM users
             WHERE username = :username
             LIMIT 1"
        )
        ->bind('username', $username)
        ->execute()
        ->current();

        if (!$user || !password_verify($data['password'], $user['password_hash'])) {
            return $this->response(
                ["success" => false, "error" => "usernameかpasswordが違います"],
                401
            );
        }
        Session::set('user_id', (int)$user['id']);

        return $this->response([
            "success" => true,
            "user" => [
                "id"       => (int)$user['id'],
                "username" => $user['username'],
                "name"     => $user['name'],
            ]
        ], 200);
    }
}
