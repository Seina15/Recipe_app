document.addEventListener("DOMContentLoaded", () => {

  function Item(name, checked = false) {
    this.Item = Item; 
    this.name = ko.observable(name);
    this.checked = ko.observable(checked);
  }


  function ShoppingListVM() {
    this.items = ko.observableArray([]);

    // 入力用関数
    this.appendItem = () => {
      const name = prompt("追加したい物を入力してください:");
      if (!name || !name.trim()) return;
      this.items.push(new Item(name.trim()));
    };

    // 削除用関数
    this.removeItem = () => {
      this.items.remove(item => item.checked());
    };
  }

  // 右のセクションのみを参照
  const right = document.querySelector(".right-section");
  if (right) {
    window.rightVM = new ShoppingListVM();
    ko.applyBindings(window.rightVM, right);

  }
});
