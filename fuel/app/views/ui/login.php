<!DOCTYPE html>

    <head>
        <meta charset="utf-8">
        <title>Login</title>
        <link rel="stylesheet" href="/assets/css/register_style.css">
    </head>

    <body>
        <h1 style="text-align: center; margin-top: 10%; font-size: 34px;">ログイン</h1>
        <section class="register-section">
            
           
            <form class="register-form"  data-bind="submit: submit">

                <p class="input-label">ユーザー名を英数字で入力してください</p>
                <input class="input-form" type="text" name="username" placeholder="ユーザー名" data-bind="value: username"><br>

                <p class="input-label">パスワードを入力してください</p>
                <input class="input-form" type="password" name="password" placeholder="Password" data-bind="value: password"><br>

                <input class="submit-button" type="submit" value="ログイン">
                <input class="create-button" type="button" value="新規登録" onclick="location.href='/home/register'">

            </form>
        </section>
        <script src="/knockout-3.2.0.js"></script>
        <script src="/assets/js/login.js"></script>
    </body>
</html>

