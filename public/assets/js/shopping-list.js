(function (global) {

  // ユーザーIDが無い時はguest
  const KEY = "shopping:" + (global.USER_ID || "guest");

  // 各項目（アイテム）に関する関数
  function Item(data) {
    this.name = ko.observable((data && data.name) || "新しい項目");
    this.checked = ko.observable(!!(data && data.checked));
  }

  // 名前の正規化に関する関数
  function normName(s) {
    let str = s || "";
    str = str.trim();
    str = str.replace(/\s+/g, " ");
    str = str.toLowerCase();
    return str;
  }


  // ローカルストレージからの読み込み・保存に関する関数
  function load() {
    try {
      let arr = [];
      let raw = localStorage.getItem(KEY);
      if (raw) {
          arr = JSON.parse(raw);
      }

      if (Array.isArray(arr)) {
        return arr.map(d => new Item(d));

      } else {
        return [];

      }
    } catch (e) {
      return [];
    }
  }

  // ローカルストレージへの保存に関する関数
  function save(items) {
    localStorage.setItem(KEY, ko.toJSON(items));
  }

  
  function ShoppingListVM() {
    const self = this;
    self.items = ko.observableArray(load());


    self.appendItem = function () {
      const name = prompt("追加したい物を入力してください:");
      if (!name || !name.trim()) {
        return;
      }
      self.addIngredient(name.trim());
    };

    self.removeItem = function () {
    let remaining = [];

    self.items().forEach(function (it) {
      if (!it.checked()) {
        remaining.push(it);
      }
    });

    self.items(remaining);
    };

    self.addIngredient = function (name) {
    const key = normName(name);
    if (!key) {
      return;
    }

    let exists = false; // 既に存在するか確認
    self.items().forEach(function (it) {
      if (normName(it.name()) === key) {
        exists = true;
      }
    });

    if (!exists) {
      const newItem = new Item({ name: name, checked: false });
      self.items.push(newItem);
    }
  };


    self.addIngredients = function (list) {
    if (!list) return;

    for (var i = 0; i < list.length; i++) {
      var entry = list[i];
      var name = "";

      if (typeof entry === "string") {
        name = entry;

      } else if (entry && typeof entry.name === "string") {
        name = entry.name;
      }
      
      if (name && name.trim() !== "") {
        self.addIngredient(name);
      }
    }
  };


    ko.computed(function () {
      save(self.items());
    });
  }

  global.App = global.App || {};
  if (!global.App.shoppingVM) {
    global.App.shoppingVM = new ShoppingListVM();
  }

  document.addEventListener("DOMContentLoaded", function () {
    const right = document.querySelector(".right-section");
    if (right && !ko.dataFor(right)) {
      ko.applyBindings(global.App.shoppingVM, right);
    }
  });

  global.rightVM = global.App.shoppingVM;
  global.addIngredientsToShopping = function (items) {
    global.App.shoppingVM.addIngredients(items);
  };
})(window);
