<!DOCTYPE html>
<html>
<head>

    <meta charset="utf-8">
    <link rel="stylesheet" href="/assets/css/home_style.css">
    <title>ホームページ</title>

</head>

<body>
    <?php echo View::forge('parts/header'); ?>

    <section class="main-sections">

        <!-- 左画面（レコメンド） -->
        <div class="left-section">
            <div class="section-header">
                <h1>Recommend</h1>
                
                <button class="icon-btn" aria-label="filter-recommend" >
                    <i class="fa-solid fa-filter"></i>
                </button>

                <button class="icon-btn" aria-label="filter-recommend" >
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
                
            </div>


            <hr class="section-div"/>
        </div>

        <!-- 右画面（ショッピングリスト） -->
        <div class="right-section">
            <div class="section-header" style="display: flex; align-items: center; gap: 20px;">
                <h1>Shopping List</h1>
                
                <button class="icon-btn" aria-label="clear-list" style="background-color: #da8660;">
                    <i class="fa-solid fa-trash-can"></i>
                </button>

                <button class="icon-btn" aria-label="add-item" style="background-color: #4CAF50;">
                    <i class="fa-solid fa-circle-plus"></i>
                </button>
            </div>

            <hr class="section-div">
            <?php echo View::forge('parts/right_section', ['items' => $items]); ?>
        </div>
    </section>
</body>
</html>