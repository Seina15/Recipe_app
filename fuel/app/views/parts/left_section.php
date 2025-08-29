<!-- ローディングとエラーメッセージ -->
<div class="loading-msg" data-bind="visible: loading">Reloading Now...</div>
<div class="error-msg" data-bind="visible: error, text: error"></div>


<!-- リロードボタンと表示件数 -->
<div class="reload-bar">
  <button class="icon-btn" data-bind="click: reloadRecipe"><i class="fa-solid fa-rotate-right"></i></button>
  <span>表示件数: <span data-bind="text: visibleCount"></span>
    /<span data-bind="text: allRecipes().length"></span></span>
</div>


<!-- メニューカード -->
<div class="menus-grid" data-bind="foreach: menus">
  <div class="menu-card">

    <label>
      <input type="checkbox"
        data-bind="checked: selected, event:{ change: function(){ $root.onMenuToggle($data); } }">
      リストに追加
    </label>
    
    <a data-bind="attr:{href: url}" target="_blank" rel="noopener" style="text-decoration:none;color:inherit;">
      <img data-bind="attr:{src: imageUrl, alt: title}, visible: imageUrl">
      <h3 data-bind="text: title"></h3>
    </a>

    <div>
      <strong>材料</strong>
      <ul data-bind="foreach: materials">
        <li data-bind="text: $data"></li>
      </ul>
    </div>

  </div>
</div>


<!-- もっと見る -->
<div class="more">
  <button class="icon-btn more-btn" data-bind="click: showMore, visible: showMore"><i class="fa-solid fa-circle-chevron-down"></i></button>
</div>
