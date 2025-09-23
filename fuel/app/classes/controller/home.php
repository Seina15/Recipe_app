<?php

class Controller_Home extends Controller
{

    // ログイン関連
    public function before()
    {
        
        parent::before();
        $public = ["login", "register", "404"];
        $action    = \Request::active()->action;
        $session_user_id = \Session::get("user_id");
        $session_userID = \Session::get("userID");
        $logged = (bool) $session_user_id;

        if (!$logged && !in_array($action, $public, true)) {
            return \Response::redirect("home/login");
        }
    }

    # ホームページ
    public function action_index()
    {
        $user_id = (int) \Session::get("user_id");
        $username = "Guest";

        if (ctype_digit((string)$user_id)) { 
            $user = Model_User::find_by_id((int)$user_id);
            if ($user && !empty($user["username"])) {
                $username = $user["username"];
            }
        }

        return \Response::forge(\View::forge("ui/index", [
            "user_id" => $user_id, 
            "username" => $username,
        ]));
    }

    # プロフィールページ
    public function action_profile()
    {
    return Response::forge(View::forge("ui/profile"));
    }

    # ユーザー登録ページ
    public function action_register()
    {
        return Response::forge(View::forge("ui/register"));
    }

    # ログインページ
    public function action_login()
    {
        return Response::forge(View::forge("ui/login"));
    }

    # 404ページ
    public function action_404()
	{
		return Response::forge("404 Not Found", 404);
	}

}