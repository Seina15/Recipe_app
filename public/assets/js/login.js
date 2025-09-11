function LoginVM(){
    const self = this;
    self.username = ko.observable("");
    self.password = ko.observable("");
    self.userId   = ko.observable(null);
    self.error    = ko.observable("");
    self.loading  = ko.observable(false);

 

    self.login = async function(){
        self.error("");
        self.loading(true);
        try {
            const res = await fetch("/api/login", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                credentials: "include",
                body: JSON.stringify({
                    username: self.username(),
                    password: self.password()
                })
            });

            const json = await res.json();
            if (!res.ok || !json.success) {
                throw new Error(json.error || "ログインに失敗しました");
            }

            self.userId(json.user.id);
            self.password("");
            window.location.href = "./profile.php"; 

        } catch(e){
            self.error(e.message);
        } finally {
            self.loading(false);
        }
    };

}
