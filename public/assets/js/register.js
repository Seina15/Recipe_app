function RegisterVM() {

  const self = this;
  self.username = ko.observable('');
  self.password = ko.observable('');
  self.userId = ko.observable(null);

  self.canSubmit = ko.pureComputed(() =>
    self.username().trim().length > 0 && self.password().length > 0
  );

  self.submit = async function () {
    const submitData = {
        username: self.username().trim(),
        password: self.password() 
    };


    const res = await fetch('/index.php/api/register/register_form.json', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify(submitData),
    });

    const data = await res.json();

    if (res.ok && data.success) {
      window.location.href = '/index.php';
    } else {
      alert('登録に失敗しました: ' + (data.error || `HTTP ${res.status}`));
    }
  };
}


ko.applyBindings(new RegisterVM(), document.querySelector('.register-section'));
