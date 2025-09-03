const form   = document.getElementById('profile-form');
const status = document.getElementById('form-status');

form.addEventListener('submit', async (e) => {
  e.preventDefault();

  // 要素はIDで安全に取得
  const avoidEl    = document.getElementById('avoid');
  const cookEl     = document.getElementById('cook_time');
  const budgetEl   = document.getElementById('budget');
  const servingsEl = document.getElementById('servings');

  // 送信データ（API側が time を期待しているなら key は time に）
  const submitData = {
    avoid:   (avoidEl?.value ?? '').trim(),
    time:     cookEl?.value ? Number(cookEl.value) : null,   // ←APIに合わせて 'time'
    budget:   budgetEl?.value ? Number(budgetEl.value) : null,
    servings: servingsEl?.value ? Number(servingsEl.value) : null,
  };

  try {
    const res = await fetch('/index.php/api/profile.json', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(submitData),
    });

    const data = await res.json(); // ← response ではなく res

    if (data.ok) {
      status.textContent = '保存が完了しました';
      setTimeout(() => (status.textContent = ''), 3000);
    } else {
      status.textContent = '保存に失敗：' + (data.error || '不明なエラー');
    }
  } catch (err) {
    status.textContent = 'エラー：' + err.message;
  }
});