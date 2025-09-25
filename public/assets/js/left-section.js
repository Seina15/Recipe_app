
// プロフィール一覧を取得する関数
function loadProfileList() {
  var select = document.getElementById("profile-filter-select");
  
  if (!select) {
    return;
  }
  
  select.innerHTML = '<option value="" disabled selected>プロフィールを選択</option>';
  var login_secret = window.LOGIN_SECRET || "";
  
  if (!login_secret) {
    return;
  }
  
  var url = "/index.php/api/profile/list?login_secret=" + encodeURIComponent(login_secret);
  
  fetch(url)
    .then(function(res) { return res.json(); })
    .then(function(json) {
      
      if (json.success && Array.isArray(json.profiles)) {
        var seen = {};
        
        for (var i = 0; i < json.profiles.length; i++) {
          var p = json.profiles[i];
          var name = "";
          
          if (typeof p.profile_name === "string") {
            name = p.profile_name.trim();
          }
          
          if (!name || name === "プロフィールを選択してください" || seen[name]) continue;
          
          seen[name] = true;
          var opt = document.createElement("option");
          opt.value = name;
          opt.textContent = name;
          select.appendChild(opt);
        }
      }
    });
}


function LeftVM() {
  var self = this;

  self.loading = ko.observable(false);
  self.error = ko.observable("");
  self.allRecipes = ko.observableArray([]);
  self.visibleCount = ko.observable(2);


  // プロフィール選択ボタン
  self.FilterRecommend = function() {
    var profileSelect = document.getElementById("profile-filter-select");
    
    if (!profileSelect) {
      return;
    }
    
    if (profileSelect.style.display === "none" || profileSelect.style.display === "") {
      loadProfileList();
      profileSelect.style.display = "inline-block";
    
    } else {
      profileSelect.style.display = "none";
    }
  };


  // キーワード検索
  self.FilterSearch = function() {
    var keyword = prompt("キーワードを入力してください:");
    
    if (!keyword || !keyword.trim()){
      return;
    }

    var user_id = window.user_id || 1;
    var url = "/index.php/api/recommend_recipe/ranking.json?user_id=" + user_id + "&keyword=" + encodeURIComponent(keyword.trim());
    self.loading(true);
    
    fetch(url)
      .then(function(response) { return response.json(); })
      .then(function(json) {
        
        if (!json.success) {
          throw new Error(json.error || "API error(RecommendRecipe)");
        }
        
        var categories = Array.isArray(json.data.categories) ? json.data.categories : [];
        var items = [];
        
        for (var i = 0; i < categories.length; i++) {
          var cat = categories[i];
        
          if (Array.isArray(cat.result)) {
            items = items.concat(cat.result);
          }
        }
        
        var mapped = [];
        
        for (var j = 0; j < items.length; j++) {
          var r = items[j];
        
          mapped.push({
            title: r.recipeTitle,
            url: r.recipeUrl,
            imageUrl: r.foodImageUrl || r.mediumImageUrl || r.smallImageUrl || "",
            materials: Array.isArray(r.recipeMaterial) ? r.recipeMaterial : [],
            selected: ko.observable(false)
          });
        }
        
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


  // 買い物リスト追加
  self.onMenuToggle = function(menu) {
    var checked = false;
    
    if (typeof menu.selected === "function") {
      checked = !!menu.selected();
    }
    
    if (checked) {
      if (Array.isArray(menu.materials) && menu.materials.length > 0) {
    
        if (typeof window.addIngredientsToShopping === "function") {
          window.addIngredientsToShopping(menu.materials);
    
        // デバッグ用
        } else {
          console.warn("addIngredientsToShopping関数が見つかりません");
        }
      }
    }
  };



  // 表示するレシピについて
  self.menus = ko.computed(function() {
    var all = self.allRecipes();
    var count = self.visibleCount();
    return all.slice(0, count);
  });



  // もっと見るボタンの表示
  self.hasMore = ko.computed(function() {
    return self.visibleCount() < self.allRecipes().length;
  });


  // レシピがないときの表示
  self.hasNoRecipes = ko.computed(function() {

    var isLoading = self.loading();
    var isError = !!self.error();
    var isEmpty = self.allRecipes().length === 0;
    
    return !isLoading && !isError && isEmpty;
  });

  
  // もっと見る
  var MoreDisplay = 12;
  self.showMore = function() {

    var next = Math.min(self.visibleCount() + MoreDisplay, self.allRecipes().length);
    self.visibleCount(next);

  };


  // レシピの再読み込み
  self.reloadRecipe = function() {
    self.error("");
    self.loading(true);
    
    var user_id = window.user_id || 1;
    var profile_name = "";
    var profileSelect = document.getElementById("profile_select");
    
    if (profileSelect) {
      profile_name = profileSelect.value;
    }
    
    var filterSelect = document.getElementById("profile-filter-select");
    
    if (filterSelect && filterSelect.style.display !== "none" && filterSelect.value) {
      profile_name = filterSelect.value;
    }
    
    var url = "/index.php/api/recommend_recipe/ranking.json?user_id=" + encodeURIComponent(user_id);
    
    if (profile_name && profile_name !== "__new__") {
      url += "&profile_name=" + encodeURIComponent(profile_name);
    }
    
    fetch(url)
      .then(function(res) { return res.text(); })
      .then(function(text) {
        var json;
        

        json = JSON.parse(text);
        

        if (!json || json.success !== true) {
          var msg = (json && (json.error || json.message)) || ("upstream http=" + ((json && json.http) || ""));
          throw new Error(msg);
        }
        
        var categories = Array.isArray(json.data && json.data.categories) ? json.data.categories : [];
        var items = [];
        
        for (var i = 0; i < categories.length; i++) {
          var cat = categories[i];
          
          if (Array.isArray(cat.result)) {
            items = items.concat(cat.result);
          }
        }
        
        var mapped = [];
        
        for (var j = 0; j < items.length; j++) {
          var r = items[j];
          
          mapped.push({
            title: r.recipeTitle,
            url: r.recipeUrl,
            imageUrl: r.foodImageUrl || r.mediumImageUrl || r.smallImageUrl || "",
            materials: Array.isArray(r.recipeMaterial) ? r.recipeMaterial : [],
            selected: ko.observable(false)
          });
        }
        
        self.error("");
        self.allRecipes(mapped);
        self.visibleCount(Math.min(self.visibleCount(), mapped.length || 2));
      })
      .catch(function(e) {
        
        console.error("recommend ranking load error:", e);
        self.error("メニュー取得のエラー： " + e.message);
        
        self.allRecipes([]);
      })
      
      .finally(function() {
        self.loading(false);
      });
  };
}


document.addEventListener("DOMContentLoaded", function() {
  var left = document.getElementById("left-section");
  
  if (left) {
    window.leftVM = new LeftVM();
    ko.applyBindings(window.leftVM, left);
    window.leftVM.reloadRecipe();
  }

});
