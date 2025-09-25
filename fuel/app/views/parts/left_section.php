<!DOCTYPE html>
<head>
  <link href="https://fonts.googleapis.com/css2?family=Kosugi+Maru&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/left_style.css">
</head>
<body>
  <!-- ローディングとエラーメッセージ -->
  <div class="loading-msg font-kosugi" data-bind="visible: loading">Reloading Now...</div>
  <div class="error-msg font-kosugi" data-bind="visible: error, text: error"></div>

  <!-- レシピが存在しないときのメッセージ -->
  <h1 class="no-recipes font-kosugi no-recipes-message" data-bind="visible: hasNoRecipes">レシピが存在しません</h1>

  <!-- リロードボタンと表示件数 -->
  <div class="reload-bar">
    <button class="icon-btn" data-bind="click: reloadRecipe"><i class="fa-solid fa-rotate-right"></i></button>
    <span class="font-kosugi recipe-count-text">表示件数: <span data-bind="text: visibleCount"></span>
     / <span data-bind="text: allRecipes().length"></span></span>
  </div>

  <!-- メニューカード -->
  <div class="menus-grid" data-bind="foreach: menus">
    <div class="menu-card">

      <label class="font-kosugi">
        <input type="checkbox"
         data-bind="checked: selected, event:{ change: function(){ $root.onMenuToggle($data); } }">
        リストに追加
      </label>
      
      <a data-bind="attr:{href: url}" target="_blank" rel="noopener" class="recipe-link">
        <img data-bind="attr:{src: imageUrl, alt: title}, visible: imageUrl">
        <h3 data-bind="text: title"></h3>
      </a>

      <div class="materials-section">
        <div></div>
        <strong class="font-kosugi materials-label">材料</strong>
        <ul class="materials-list" data-bind="foreach: materials">
          <li data-bind="text: $data" class="font-kosugi"></li>
        </ul>
      </div>

    </div>
  </div>

  <!-- もっと見る -->
  <div class="more">
    <button class="icon-btn more-btn" data-bind="click: showMore, visible: showMore"><i class="fa-solid fa-circle-chevron-down"></i></button>
  </div>

</body>
</html>