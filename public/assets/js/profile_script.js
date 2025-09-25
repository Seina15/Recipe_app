
// トースト表示関数
function showToast(message) {
  var toast = document.createElement("div");
  toast.className = "profile-toast";
  toast.textContent = message;
  document.body.appendChild(toast);
  
  setTimeout(function() {
    toast.style.opacity = 0;
    setTimeout(function() {
      toast.remove();
    }, 500);
  }, 3000);
}


var form = document.getElementById("profile-form");
if (!window.__PROFILE_SUBMIT_BOUND__) {
  window.__PROFILE_SUBMIT_BOUND__ = true;
  
  var profileSelectEl = document.getElementById("profile_select");
  var profileNameEl = document.getElementById("profile_name");
  var avoidElement = document.getElementById("avoid");
  var cookElement = document.getElementById("cook_time");
  var budgetElement = document.getElementById("budget");


  // プロフィール名の正規化
  function normalizeProfileName(raw) {
    if (typeof raw !== "string") return "";
    var s = raw.trim();
    s = s.replace(/\u3000/g, " ");
    s = s.replace(/\s+/g, " ");
    return s;
  }


  // ログインシークレットの取得(セキュリティ関連)
  function getLoginSecret() {
    var h = document.getElementById("login_secret");
    if (h && typeof h.value === "string" && h.value.trim() !== "") {
      return h.value.trim();
    }
    if (typeof window !== "undefined" && typeof window.LOGIN_SECRET === "string") {
      return window.LOGIN_SECRET;
    }
    return null;
  }


  // JSON取得用関数
  function fetchJson(url, callback) {
    fetch(url, {
      method: "GET",
      headers: { "Accept": "application/json" },
      credentials: "include"
    })
    .then(function(response) {
      return response.json();
    })
    .then(function(data) {
      callback(data);
    })
    .catch(function() {
      callback(null);
    });
  }
  

  // プロフィール一覧の読み込み
  function loadProfileList(callback) {
    var secret = getLoginSecret();
    
    if (!secret){
      return;
    }

    var url = "/api/profile/list?login_secret=" + encodeURIComponent(secret);
    fetchJson(url, function(data) {
      if (!data || !data.success){
        return;
      }
      
      if (profileSelectEl) {
        
        while (profileSelectEl.firstChild) profileSelectEl.removeChild(profileSelectEl.firstChild);
        
        var placeholder = document.createElement("option");
        placeholder.value = "";
        placeholder.textContent = "選択してください";
        placeholder.disabled = true;
        placeholder.selected = true;
        profileSelectEl.appendChild(placeholder);
        
        for (var i = 0; i < data.profiles.length; i++) {
          var p = data.profiles[i];
          var opt = document.createElement("option");
          opt.value = p.profile_name;
          opt.textContent = p.profile_name;
          profileSelectEl.appendChild(opt);
        }
        
        var newOpt = document.createElement("option");
        
        newOpt.value = "__new__";
        newOpt.textContent = "＋ 新規作成…";
        profileSelectEl.appendChild(newOpt);
      }
      if (typeof callback === "function"){
        callback();
      }
    });
  }
  


  // プロフィール情報の読み込み
  function loadProfileByName(name) {
    var secret = getLoginSecret();
    if (!secret){
      return;
    }

    var url = "/api/profile/view?login_secret=" + encodeURIComponent(secret) + "&name=" + encodeURIComponent(name);
    fetchJson(url, function(data) {
      if (!data || !data.success) return;
      if (profileNameEl) {
        profileNameEl.value = name;
      }

      if (avoidElement) {
        if (data.profile && typeof data.profile.avoid === "string") {
          avoidElement.value = data.profile.avoid;
        } else {
          avoidElement.value = "";
        }
      }

      if (cookElement) {
        if (data.profile && data.profile.cook_time !== null && data.profile.cook_time !== undefined) {
          cookElement.value = String(data.profile.cook_time);
        } else {
          cookElement.value = "";
        }
      }

      if (budgetElement) {
        if (data.profile && data.profile.budget !== null && data.profile.budget !== undefined) {
          budgetElement.value = String(data.profile.budget);
        } else {
          budgetElement.value = "";
        }
      }
    });
  }



  // ぷｒ登録
  if (profileSelectEl) {
    profileSelectEl.addEventListener("change", function() {
      var v = profileSelectEl.value;
      if (v === "__new__") {
        if (profileNameEl) {
          profileNameEl.value = "";
          profileNameEl.focus();
        }
        if (avoidElement){
          avoidElement.value  = "";
        }
        if (cookElement){
          cookElement.value   = "";
        }
        if (budgetElement) {
          budgetElement.value = "";
        }

      } else if (v) {
        loadProfileByName(v);
      }
    });
  }



  // 初期化
  loadProfileList(function() {
    if (profileSelectEl) {
      var hasDefault = false;
      
      for (var i = 0; i < profileSelectEl.options.length; i++) {
      
        if (profileSelectEl.options[i].value === "Default") {
          hasDefault = true;
          break;
        }
      }
      
      if (hasDefault) {
        profileSelectEl.value = "Default";
        loadProfileByName("Default");
      }
    }
  });

  

  // フォーム送信処理
  form.addEventListener("submit", function(e) {
  e.preventDefault();

  var loginSecret = getLoginSecret();
  if (!loginSecret) {
    alert("ログイン情報がありません。再ログインしてください。");
    return;
  }

  var profile_name = "Default";
  if (profileNameEl && typeof profileNameEl.value === "string") {
    profile_name = normalizeProfileName(profileNameEl.value);
  
  }
  if (profile_name === "") {
    if (profileNameEl) {
      profileNameEl.focus();
    }
    return;
  }
  
  if (profile_name.length > 100) {
    if (profileNameEl) {
      profileNameEl.focus();
    }
    return;
  }

  var submitData = {};
  submitData.login_secret = loginSecret;
  submitData.profile_name = profile_name;


  // avoid
  if (avoidElement && typeof avoidElement.value === "string") {
    var avoid = avoidElement.value.trim();
  
    if (avoid !== "") {
      submitData.avoid = avoid;
    }
  }

  // cook_time
  if (cookElement && typeof cookElement.value === "string") {
    var cookValue = cookElement.value.trim();
  
    if (cookValue !== "") {
      var cookNum = parseInt(cookValue, 10);
  
      if (!isNaN(cookNum) && cookNum >= 0) {
        submitData.cook_time = cookNum;
      }
    }
  }

  // budget
  if (budgetElement && typeof budgetElement.value === "string") {
    var budgetValue = budgetElement.value.trim();
  
    if (budgetValue !== "") {
      var budgetNum = parseInt(budgetValue, 10);
  
      if (!isNaN(budgetNum) && budgetNum >= 0) {
        submitData.budget = budgetNum;
      }
    }
  }

  fetch("/api/profile", {
    method: "POST",
    headers: {
      "Content-Type": "application/json; charset=utf-8",
      "Accept": "application/json"
    },
    credentials: "include",
    body: JSON.stringify(submitData)
  })

  .then(function(response) {
    return response.json();
  })

  .then(function(data) {

    if (data && data.success) {
      showToast("保存されました");
      loadProfileList(function() {

        if (profileSelectEl) {
          profileSelectEl.value = profile_name;
        }
      });

    } else {
      alert("保存に失敗しました: " + (data && data.error ? data.error : "不明なエラー"));
    }
  })

  .catch(function(e) {
    alert("通信エラー: " + e);
  });
});
}