document.addEventListener("DOMContentLoaded", () => {

  // name: アイテム名,　checked:チェックボックスの状態
  function Item(name, checked = false) {
    this.name = ko.observable(name);
    this.checked = ko.observable(checked);
  }

  function AppVM() {
    this.items = ko.observableArray([]);

    // 追加
    this.appendItem = () => {
      const name = prompt("追加したい物を入力してください:");

      if (!name || !name.trim()) return;
      this.items.push(new Item(name.trim()));
    };

    // ゴミ箱 (チェック済みだけ削除)
    this.removeItem = () => {
      this.items.remove(item => item.checked());
    };
  }

  // 起動
  ko.applyBindings(new AppVM());
});
