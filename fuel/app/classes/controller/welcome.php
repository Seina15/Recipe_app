
<?php

class Controller_Welcome extends Controller
{
	public function action_index()
	{
		return Response::forge(View::forge('welcome/index'));
	}

	// Helloページ（Presenter使用例）
	public function action_hello()
	{
		return Response::forge(Presenter::forge('welcome/hello'));
	}

	// 404ページ
	public function action_404()
	{
		return Response::forge(Presenter::forge('welcome/404'), 404);
	}
}
