<!DOCTYPE html>

    <head>
        <meta charset="utf-8">
        <title>ログイン
        </title>
        <link rel="stylesheet" href="/assets/css/register_style.css">
        <link href="https://fonts.googleapis.com/css2?family=M+PLUS+Rounded+1c:wght@400;700&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Kosugi+Maru&display=swap" rel="stylesheet">
    </head>

    <body >
        <h1 class="login-title">Login</h1>
        <section class="register-section">
            <div class="error-msg" data-bind="visible: error, text: error"></div>

            <form class="register-form"  data-bind="submit: login">

                <p class="input-label">ユーザー名を英数字で入力してください</p>
                <input class="input-form" type="text" name="username" placeholder="ユーザー名" data-bind="value: username" autocomplete="username"><br>

                <p class="input-label">パスワードを入力してください</p>
                <input class="input-form" type="password" name="password" placeholder="Password" data-bind="value: password" autocomplete="current-password"><br>

                <input class="submit-button" type="submit" value="ログイン">
                <input class="create-button" type="button" value="新規登録" onclick="location.href='/home/register'">

            </form>
        </section>
    <script src="/knockout-3.2.0.js"></script>
    <script src="/assets/js/login.js"></script>
         <script>
            document.addEventListener("DOMContentLoaded", function () {
                ko.applyBindings(new LoginVM(), document.querySelector(".register-section"));
            });
        </script>
    </body>
</html>

