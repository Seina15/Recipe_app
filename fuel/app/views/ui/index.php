<!DOCTYPE html>
<html>
<head>

    <meta charset="utf-8">
    <link rel="stylesheet" href="/assets/css/home_style.css">
    <title>ホームページ</title>

</head>

<body>
    <?php echo View::forge('parts/header'); ?>
    <section>
    <section class="main-sections">

        <div class="left-section">

            <!-- 左画面（レコメンド） -->
            <h1 style="font-size: 30px; margin-left: 20px;">Recommend</h1>
            <hr class="section-div"/>
            
            </div>

            <!-- 右画面（ショッピングリスト） -->
            <div class="right-section">
                <h1 style="font-size: 30px;">Shopping List</h1>
                <hr class="section-div"/>
                <?php echo View::forge('parts/right_section', ['items' => $items]); ?>

            </div>
    </section>
