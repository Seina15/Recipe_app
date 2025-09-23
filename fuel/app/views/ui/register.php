<!DOCTYPE html>

    <head>
        <meta charset="utf-8">
        <title>Register</title>
        <link rel="stylesheet" href="/assets/css/register_style.css">
        <link href="https://fonts.googleapis.com/css2?family=M+PLUS+Rounded+1c:wght@400;700&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Kosugi+Maru&display=swap" rel="stylesheet">
    </head>

    <body>
        <h1 style="text-align: center; margin-top: 10%; font-size: 34px;">Create Account</h1>
        <section class="register-section">
            <div class="error-msg" data-bind="visible: error, text: error" style="color: rgba(181, 47, 47, 1)"></div>           
            <form class="register-form"  data-bind="submit: submit">

                <p class="input-label">ユーザー名を英数字で入力してください</p>
                <input class="input-form" type="text" name="username" placeholder="ユーザー名" data-bind="value: username"><br>

                <p class="input-label">パスワードを入力してください</p>
                <input class="input-form" type="password" name="password" placeholder="Password" data-bind="value: password"><br>

                <input class="submit-button" type="submit" value="登録">
                <input class="login-button" type="button" value="ログイン" onclick="location.href='/home/login'">

            </form>
        </section>
    <script src="/knockout-3.2.0.js"></script>
    <script src="/assets/js/register.js"></script>
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                ko.applyBindings(new RegisterVM(), document.querySelector(".register-section"));
            });
        </script>
    </body>
</html>

