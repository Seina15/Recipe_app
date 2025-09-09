(function () {
  function LeftVM () {

    let self = this;
    self.loading = ko.observable(false); //状態
    self.error   = ko.observable(''); // エラーメッセージ
    self.allRecipes   = ko.observableArray([]); // レシピの配列
    self.visibleCount = ko.observable(2); // 表示件数

    const MoreDisplay = 12; 
    const DEFAULT_CATEGORY_ID = '';
  
    // もっと見る処理
    self.onMenuToggle = function(menu) {
      if (!window.rightVM) return; 
      self.AddItemFromRecipe();
    };


    // 選択したレシピの材料をお買い物リストに反映する関数
    self.AddItemFromRecipe = function() {

      if (!window.rightVM) return;

      // 選択したレシピの抽出
      var allMenus = self.allRecipes();
      var selectedMenus = []; 

      for (var i = 0; i < allMenus.length; i++) {
        var menu = allMenus[i];

        if (menu.selected && menu.selected() === true) {
          selectedMenus.push(menu);
        }
      }


      // 材料の重複判定
      var uniqueItem = Object.create(null);
      selectedMenus.forEach(function(menu) {

        var materials = menu.materials || [];

        // 材料の正規化
        materials.forEach(function(material) {
          var text = material ? material.toString() : "";
          text = text.trim();
          text = text.toLowerCase();
          text = text.replace(/\s+/g, " ");

          if (text.length > 0) {
            uniqueItem[text] = material;
          }
       });

      });

      // リストの再生成 & アイテムの追加
      window.rightVM.items.removeAll();
      var make;
      if (window.rightVM && typeof window.rightVM.createItem === 'function') {
        make = window.rightVM.createItem.bind(window.rightVM);

      } else {
        make = function(name) {
          return {
            name: ko.observable(name),
            checked: ko.observable(false)
          };
        };
      }

      var keys = Object.keys(uniqueItem);
      for (var i = 0; i < keys.length; i++) {
        var key = keys[i];
        var originalText = uniqueItem[key];
        var item = make(originalText);
        window.rightVM.items.push(item);
      }

    };


    
    self.menus = ko.pureComputed(function () {
      return self.allRecipes().slice(0, self.visibleCount());
    });

    // もっと表示できるかの判定をする
    self.hasMore = ko.pureComputed(function () {
      return self.visibleCount() < self.allRecipes().length;
    });

    // MoreDisplay分表示を増やす
    self.showMore = function () {
      var next = Math.min(self.visibleCount() + MoreDisplay, self.allRecipes().length);
      self.visibleCount(next);
    };


   

    self.reloadRecipe = function () {
      self.error('');
      self.loading(true);

      var userId = window.USER_ID || 1;
      var url = '/index.php/api/recipe/ranking.json?userId=' + userId;

      if (DEFAULT_CATEGORY_ID) {
        url += '&categoryId=' + encodeURIComponent(DEFAULT_CATEGORY_ID);
      }


      return fetch(url)
        .then(function(response) {
          return response.text().then(function(text) {
            console.debug("[ranking] http=", response.status, "raw:", text.slice(0, 300));


            var json;
            try {
              json = JSON.parse(text);
            } catch (e) {
              throw new Error('non-json: ' + text.slice(0, 200));
            }

            var okFlag = (json && json.success === true);

            // エラーメッセージの作成
            if (!okFlag) {
              var msg = "サーバーエラー";
              if (json && (json.error || json.message)) {
                msg = json.error || json.message;

              } else if (json && json.http != null) {
                msg = "upstream http=" + json.http;

              } else {
                msg = "upstream http=" + response.status;
              }

              throw new Error(msg);
            }
            // 正常に動作している場合
            return json;
            
          });
        })


        .then(function(json) {
          var data = json.data || {};
          var items;


          if (Array.isArray(data.result)) {
            items = data.result;
          } else {
            items = [];
          }


          if (items.length === 0) {
            throw new Error("ランキングの読み込みエラー");
          }
          

          // 受け取った情報の整形
          var mapped = items.map(function(r) {
            return {
              title: r.recipeTitle,
              url: r.recipeUrl,
              imageUrl: r.foodImageUrl || r.mediumImageUrl || r.smallImageUrl || "",
              materials: Array.isArray(r.recipeMaterial) ? r.recipeMaterial : [],
              selected: ko.observable(false)
            };
          });

          // UIの更新
          self.allRecipes(mapped);

        })
        .catch(function(e) {
          console.error("ranking_load_error:", e);
          self.error("メニュー取得のエラー： " + e.message);
          self.allRecipes([]); 
        })
        .finally(function() {
          self.loading(false);
        });
    };

  }

  document.addEventListener('DOMContentLoaded', function () {
    var left = document.getElementById('left-section');
    if (left) {
      window.leftVM = new LeftVM();
      ko.applyBindings(window.leftVM, left);
      window.leftVM.reloadRecipe();
    }
  });
})();
