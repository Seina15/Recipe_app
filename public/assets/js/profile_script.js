const form   = document.getElementById("profile-form");
const status = document.getElementById("form-status");

form.addEventListener("submit", async (e) => {
  e.preventDefault();

  const avoidElement    = document.getElementById("avoid");
  const cookElement     = document.getElementById("cook_time");
  const budgetElement   = document.getElementById("budget");
  const servingsElement = document.getElementById("servings");


  const submitData = {};

  var avoid = "";
  if (avoidElement && typeof avoidElement.value === "string") {
    avoid = avoidElement.value.trim();
  }
  if (avoid !== "") {
    submitData.avoid = avoid;
  }

  function toInt(el) {
    var v = "";
    if (el && typeof el.value === "string") {
      v = el.value.trim();
    }
    if (v === "") {
      return undefined;
    }
    var n = parseInt(v, 10);
    if (isNaN(n)) {
      return undefined;
    }
    return n;
  }

  var t = toInt(cookElement);
  var b = toInt(budgetElement);
  var s = toInt(servingsElement);

  if (typeof t === "number") {
    submitData.time = t;
  }
  if (typeof b === "number") {
    submitData.budget = b;
  }
  if (typeof s === "number") {
    submitData.servings = s;
  }

  try {
    const res = await fetch("/index.php/api/profile.json", {
      method: "POST",
      headers: { "Content-Type": "application/json", "Accept": "application/json" },
      body: JSON.stringify(submitData),
      credentials: "include",
    });

    const text = await res.text();
    let data;
    try { data = JSON.parse(text); } catch { data = { success: false, error: text }; }

    if (res.ok && data.success) {
      status.textContent = "保存が完了しました。";
      setTimeout(() => (status.textContent = ""), 3000);
    } else {
      status.textContent = "保存に失敗しました。" + (data.error || `HTTP ${res.status}`);
    }
  } catch (err) {
    status.textContent = "エラー：" + err.message;
  }
});
