<!DOCTYPE html>
<html>
<head>

    <meta charset="utf-8">
    <link rel="stylesheet" href="/assets/css/home_style.css">
    <title>ホームページ</title>
    <script>
        window.USER_ID = <?= (int)$user_id ?? 1 ?>;　// ユーザーIDをJavaScriptに渡す（テスト用！後で変える）
    </script>

</head>

<body>
    <?php echo View::forge('parts/header'); ?>

    <section class="main-sections">

        <!-- Left Section -->
        <div class="left-section" id ="left-section">
            <div class="section-header">
                <h1>Recommend</h1>
                
                <button class="icon-btn" id="filter-recommend" >
                    <i class="fa-solid fa-filter"></i>
                </button>

                <button class="icon-btn" id="filter-search" >
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>

                
            </div>
            <hr class="section-div"/>
            <?php echo View::forge('parts/left_section'); ?>
        </div>

        <!--Right Section-->
        <div class="right-section">
            <div class="section-header" style="display: flex; align-items: center; gap: 20px;">
                <h1>Shopping List</h1>

                <button class="icon-btn" id="clear-btn" data-bind="click: removeItem" style="background-color:#da8660;">
                    <i class="fa-solid fa-trash-can"></i>
                </button>

                <button class="icon-btn" id="add-btn" data-bind="click: appendItem" style="background-color:#4CAF50;">
                    <i class="fa-solid fa-circle-plus"></i>
                </button>
            </div>

            <hr class="section-div">

            <!-- Shopping-List -->
            <ul class="shopping-list" data-bind="foreach: items">
            <li>
                <input type="checkbox" data-bind="checked: checked, attr: { id: 'item-' + $index() }">
                <label data-bind="text: name, attr: { for: 'item-' + $index() }"></label>
            </li>
            </ul>

        </div>
    </section>


    <script src="/knockout-3.2.0.js"></script>
    <script src="/assets/js/shopping-list.js"></script>
    <script src="/assets/js/left-section.js"></script>
</body>
</html>