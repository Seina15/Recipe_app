(function () {

  // プロフィール一覧を取得する関数
  function loadProfileList() {
    const select = document.getElementById("profile-filter-select");

    if (!select){
      return;
    }

  select.innerHTML = '<option value="" disabled selected>プロフィールを選択</option>';
  const login_secret = window.LOGIN_SECRET || "";

  if (!login_secret) {
    return;
  }

  fetch("/index.php/api/profile/list?login_secret=" + encodeURIComponent(login_secret))
    .then(res => res.json())
    .then(json => {
      if (json.success && Array.isArray(json.profiles)) {
        const seen = new Set();
        
        json.profiles.forEach(p => {
          let name;
          
          if (typeof p.profile_name === "string") {
            name = p.profile_name.trim();
          
          } else {
            name = "";
          }

          if (!name || name === "プロフィールを選択してください" || seen.has(name)) return;
          seen.add(name);
          const opt = document.createElement("option");
          opt.value = name;
          opt.textContent = name;
          select.appendChild(opt);
        });
      }
    });
  }




  function LeftVM () {
    let self = this;

    // レシピ取得用関数
    self.FilterRecommend = function() {
      const profileSelect = document.getElementById("profile-filter-select");
      
      if (!profileSelect){
        return;
      }

      if (profileSelect.style.display === "none" || profileSelect.style.display === "") {
        loadProfileList();
        profileSelect.style.display = "inline-block";
      
      } else {
        profileSelect.style.display = "none";
      }
    };

    self.loading      = ko.observable(false);
    self.error        = ko.observable("");
    self.allRecipes   = ko.observableArray([]);
    self.visibleCount = ko.observable(2);


    // キーワード検索に関する関数
    self.FilterSearch = function() {
    const keyword = prompt("キーワードを入力してください:");
    if (!keyword || !keyword.trim()) return;

    const user_id = window.user_id || 1;
    const url = "/index.php/api/recommend_recipe/ranking.json?user_id=" + user_id +
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


      const user_id = window.user_id || 1;
      let profile_name = "";
      let profileSelect = document.getElementById("profile_select");
      
      if (profileSelect) {
        profile_name = profileSelect.value;
      }
      
      let filterSelect = document.getElementById("profile-filter-select");
      
      if (filterSelect && filterSelect.style.display !== "none" && filterSelect.value) {
        profile_name = filterSelect.value;
      }
      
      // プロフィール未選択時（デフォルト表示）
      let url = "/index.php/api/recommend_recipe/ranking.json?user_id=" + encodeURIComponent(user_id);
      if (profile_name && profile_name !== "__new__") {
        url += "&profile_name=" + encodeURIComponent(profile_name);
      }

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
