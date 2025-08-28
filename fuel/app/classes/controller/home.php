<?php

class Controller_Home extends Controller
{
    # ホームページ
    public function action_index()
    {
        return Response::forge(View::forge('ui/index'));
    }

    # プロフィールページ
    public function action_profile()
    {
        return Response::forge(View::forge('ui/profile'));
    }

    # 404ページ
    public function action_404()
	{
		return Response::forge('404 Not Found', 404);
	}
    

}