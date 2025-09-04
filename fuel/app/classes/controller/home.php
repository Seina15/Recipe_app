<?php

class Controller_Home extends Controller
{
    # ホームページ
    public function action_index()
    {
        $user_id = 1; // 仮のユーザーID（後で変える）
        // Viewの呼び出しとIDをわたす
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

    # 404ページ
    public function action_404()
	{
		return Response::forge('404 Not Found', 404);
	}

}