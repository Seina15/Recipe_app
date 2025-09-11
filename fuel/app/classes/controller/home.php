<?php

class Controller_Home extends Controller
{

    public function before()
    {
        parent::before();
        $public = ["login", "register", "404"];
        $action    = \Request::active()->action;
        $logged = (bool) \Session::get('user_id');

        if (!$logged && !in_array($action, $public, true)) {
            return \Response::redirect('home/login');
        }
    }

    # ホームページ
    public function action_index()
    {
        $user_id = (int) \Session::get('user_id');
        return \Response::forge(\View::forge('ui/index', [
            'user_id' => $user_id, 
        ]));
    }

    # プロフィールページ
    public function action_profile()
    {
        return Response::forge(View::forge('ui/profile'));
    }

    # ユーザー登録ページ
    public function action_register()
    {
        return Response::forge(View::forge('ui/register'));
    }
    public function action_login()
    {
        return Response::forge(View::forge('ui/login'));
    }

    # 404ページ
    public function action_404()
	{
		return Response::forge('404 Not Found', 404);
	}

}