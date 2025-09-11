function RegisterVM() {
  const self = this;
  self.username = ko.observable("");
  self.password = ko.observable("");
  self.error    = ko.observable("");
  self.loading  = ko.observable(false); 


  self.ableSubmit = ko.pureComputed(function () {
    const name = self.username().trim().length > 0;
    const pass = self.password().length > 0;
    return name && pass;
  });

  self.submit = async function () {
    self.error("");

    if (!self.ableSubmit()) {
      self.error("ユーザー名とパスワードを入力してください");
      return;
    }


    const SubmitData = {
      username: self.username().trim(),
      password: self.password()
    };

    self.loading(true); 
    try {
      const res = await fetch("/index.php/api/register/register_form.json", {
        method: "POST",
        headers: { "Content-Type": "application/json", "Accept": "application/json" },
        body: JSON.stringify(SubmitData)
      });

      let data;
      const contentType = res.headers.get("content-type") || "";
      if (contentType.includes("application/json")) {
        data = await res.json();

      if (res.ok && data && data.success) {
        window.location.href = "/index.php";

      } else {
        self.error((data && data.error) || "登録に失敗しました");
      }

    } else {
      self.error("サーバーエラーが発生しました");
    }

    } catch (e) {
      self.error(e.message || "エラーが発生しました");
  
    }finally {
      self.loading(false);
    }
  };
}
