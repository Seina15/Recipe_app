<?php

class Controller_Home extends Controller
{
    # ホームページ
    public function action_index()
    {
        $items = ['玉ねぎ', 'にんじん', 'じゃがいも', '牛肉'];
        return Response::forge(View::forge('ui/index', ['items' => $items]));
    }

    # プロフィールページ
    public function action_profile()
    {
        return Response::forge(View::forge('ui/profile'));
    }

    # 404ページ
    public function action_404()
	{
		return Response::forge(Presenter::forge('welcome/404'), 404);
	}
    

}