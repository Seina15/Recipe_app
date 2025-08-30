<!DOCTYPE html>

<head>
    <link rel="stylesheet" href="/assets/css/profile_style.css">
</head>

<body>
    <h1 class = "profile-title">Profile Setting</h1>
    <hr/>

    <section class="section-profile">
    <form id="profile-form" class="profile-form" autocomplete="on">

    
        <!-- Foods to Avoid（アレルギー・苦手な物） -->
        <div class="row">
            <label for="avoid">苦手な物・アレルギー等</label>
            <input class="input-box" name="avoid" type="text" placeholder="例：ピーナッツ、エビ"/>
        </div>



        <!-- 調理時間 -->
        <div class="row">
        <label for="time">調理時間の上限</label>
        <select class="input-box" name="time">
            <option value="">Select…</option>
            <option value="15">~ 15 分</option>
            <option value="30">~ 30 分</option>
            <option value="45">~ 45 分</option>
            <option value="60">~ 60 分</option>
            <option value="90">指定しない</option>
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
        <select class="input-box" name="servings">
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
        <button type="reset">リセット</button>
        <button type="submit">保存</button>
        </div>

    </form>

</section>
</body>