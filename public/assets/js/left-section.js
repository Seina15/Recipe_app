(function () {
  function LeftVM () {
    let self = this;


    // おすすめ検索ボタン用（今後実装予定）
    self.FilterRecommend = function() {
      alert("おすすめ検索機能(開発中)");
    };


    self.loading      = ko.observable(false);
    self.error        = ko.observable("");
    self.allRecipes   = ko.observableArray([]);
    self.visibleCount = ko.observable(2);


    // キーワード検索に関する関数
    self.FilterSearch = function() {
    const keyword = prompt("キーワードを入力してください:");
    if (!keyword || !keyword.trim()) return;

    const userId = window.USER_ID || 1;
    const url = "/index.php/api/recommend_recipe/ranking.json?userId=" + userId +
      "&keyword=" + encodeURIComponent(keyword.trim());

      self.loading(true);
      fetch(url)
        .then(function(response) {
          return response.json();
        })
        .then(function(json) {
          if (!json.success) {
            throw new Error(json.error || "API error(RecommendRecipe)");
          }
  
          var categories = Array.isArray(json.data.categories) ? json.data.categories : [];

          var items = [];
          categories.forEach(function(cat) {
            if (Array.isArray(cat.result)) {
              items = items.concat(cat.result);
            }
          });


          var mapped = items.map(function(r) {
            return {
              title: r.recipeTitle,
              url: r.recipeUrl,
              imageUrl: r.foodImageUrl || r.mediumImageUrl || r.smallImageUrl || "",
              materials: Array.isArray(r.recipeMaterial) ? r.recipeMaterial : [],
              selected: ko.observable(false)
            };
          });


          self.error("");
          self.allRecipes(mapped);
          var newCount = mapped.length > 0 ? Math.min(self.visibleCount(), mapped.length) : 2;
          self.visibleCount(newCount);
        })

        .catch(function(e) {
          console.error("FilterSearch error:", e);
          self.error("検索エラー: " + e.message);
          self.allRecipes([]);
        })
        .finally(function() {
          self.loading(false);
        });
    };


    // 買い物リスト追加用関数
    self.onMenuToggle = function (menu) {
      var checked = false;
      if (typeof menu.selected === "function") {
        checked = !!menu.selected();
      }

      if (checked) {
        if (Array.isArray(menu.materials) && menu.materials.length > 0) {
          if (typeof window.addIngredientsToShopping === "function") {
            window.addIngredientsToShopping(menu.materials);
          } else {
            console.warn("addIngredientsToShopping が見つかりません");
          }
        }
      }
    };




    // 表示するレシピに関する関数
    self.menus = ko.computed(function () {
      var all = self.allRecipes();
      var count = self.visibleCount();
      return all.slice(0, count);
    });

    // もっと見るボタンの表示判定関数
    self.hasMore = ko.computed(function () {
      return self.visibleCount() < self.allRecipes().length;
    });

    // レシピがないときの表示判定関数
    self.hasNoRecipes = ko.computed(function () {
      var isLoading = self.loading();
      var isError = !!self.error();
      var isEmpty = self.allRecipes().length === 0;
      return !isLoading && !isError && isEmpty;
    });


    // 「もっとみる」1回に増やす件数
    const MoreDisplay = 12;


    // もっと見るに関する関数
    self.showMore = function () {
      const next = Math.min(self.visibleCount() + MoreDisplay, self.allRecipes().length);
      self.visibleCount(next);
    };


    // レシピの再読み込み関数
    self.reloadRecipe = function () {
      self.error("");
      self.loading(true);

      const userId = window.USER_ID || 1;
      const url = "/index.php/api/recommend_recipe/ranking.json?userId=" + userId;

      return fetch(url)
        .then(res => res.text().then(text => {
          let json;
          try { json = JSON.parse(text); }
          catch { throw new Error("non-json: " + text.slice(0, 200)); }

          if (!json || json.success !== true) {
            const msg = json?.error || json?.message || ("upstream http=" + (json?.http ?? res.status));
            throw new Error(msg);
          }
          return json;
        }))
        .then(json => {
          const categories = Array.isArray(json.data?.categories) ? json.data.categories : [];
          const items = categories.flatMap(cat => Array.isArray(cat.result) ? cat.result : []);

          const mapped = items.map(r => ({
            title: r.recipeTitle,
            url: r.recipeUrl,
            imageUrl: r.foodImageUrl || r.mediumImageUrl || r.smallImageUrl || "",
            materials: Array.isArray(r.recipeMaterial) ? r.recipeMaterial : [],
            selected: ko.observable(false),
          }));

          self.error("");
          self.allRecipes(mapped);
          self.visibleCount( Math.min(self.visibleCount(), mapped.length || 2) );
        })
        .catch(e => {
          console.error("recommend_ranking_load_error:", e);
          self.error("メニュー取得のエラー： " + e.message);
          self.allRecipes([]);
        })
        .finally(() => self.loading(false));
    };
  }

  
  document.addEventListener("DOMContentLoaded", function () {
    const left = document.getElementById("left-section");
    if (left) {
      window.leftVM = new LeftVM();
      ko.applyBindings(window.leftVM, left);
      window.leftVM.reloadRecipe();
    }
  });
})();
