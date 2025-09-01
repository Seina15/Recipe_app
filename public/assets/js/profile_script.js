const form   = document.getElementById('profile-form');
const status = document.getElementById('form-status');


form.addEventListener('submit', async function (e) {
    e.preventDefault();

    // 送信データ
    const submitData = {
        avoid:    form.avoid.value.trim(),
        time:     form.time.value,
        budget:   form.budget.value,
        servings: form.servings.value
    };


    try {
        // リクエストの送信
        const response = await fetch("/api/profile", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(submitData)
        });


        // レスポンスの処理（Statusの作成）
        const data = await response.json();
        status.textContent = "";

        if (data.ok) {
            status.textContent = "保存が完了しました";
            setTimeout(() => status.textContent = "", 3000);

        } else {
            status.textContent = "保存に失敗：" + (data.error || "不明なエラー");
        }

    } catch (err) {
        status.textContent = "エラー：" + err.message;
    }
});

