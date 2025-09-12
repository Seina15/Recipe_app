<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="utf-8">
        <title>Profile Setting</title>
        <link rel="stylesheet" href="/assets/css/profile_style.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
        <link href="https://fonts.googleapis.com/css2?family=M+PLUS+Rounded+1c:wght@400;700&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Kosugi+Maru&display=swap" rel="stylesheet">

    </head>

    <body>

        <header>
            <h1 class="header-title">Profile Setting</h1>
            <a id="home-icon" href="/home/profile">
                <i class="fa-solid fa-house"></i>
            </a>
        </header>


        <div class = "profile-center-wrapper">
            <section class="section-profile">
                <form id="profile-form" class="profile-form" autocomplete="on">

                <!-- Foods to Avoid（アレルギー・苦手な物） -->
                <div class="row">
                    <label for="avoid">苦手な物・アレルギー等</label>
                    <input class="input-box" id="avoid" name="avoid" type="text" placeholder="例：ピーナッツ、エビ"/>
                </div>

                <!-- 調理時間 -->
                <div class="row">
                    <label for="time">調理時間の上限</label>
                    <select class="input-box" id="time" name="time">
                    <option value="">選択してください…</option>
                    <option value="15">~ 15 分</option>
                    <option value="30">~ 30 分</option>
                    <option value="45">~ 45 分</option>
                    <option value="60">~ 60 分</option>
                    <option value="">指定しない</option>
                    </select>
                </div>

                <!-- 予算 -->
                <div class="row">
                    <label for="budget">予算</label>
                    <input class="input-box" id="budget" name="budget" type="number" min="0" step="100" placeholder="1000 (円)"/>
                </div>

                <!-- 人数 -->
                <div class="row">
                    <label for="servings">人数</label>
                    <select class="input-box" id="servings" name="servings">
                    <option value="">選択してください…</option>
                    <option value="1">1 人</option>
                    <option value="2">2 人</option>
                    <option value="3">3 人</option>
                    <option value="4">4 人</option>
                    <option value="5">5 人以上</option>
                    </select>
                </div>

                <!-- 保存 / リセット -->
                <div class="actions">
                    <button type="reset" class = "reset-button">リセット</button>
                    <button type="submit" class = "submit-button">保存</button>
                    <p id="form-status" class="status" aria-live="polite"></p>
                </div>
                </form>
            </section>
        </div>

        <script src="/assets/js/profile_script.js" defer></script>
    </body>
</html>
