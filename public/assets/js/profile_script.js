const form   = document.getElementById('profile-form');
const status = document.getElementById('form-status');

form.addEventListener('submit', async (e) => {
  e.preventDefault();


  const avoidElement    = document.getElementById('avoid');
  const cookElement     = document.getElementById('cook_time');
  const budgetElement   = document.getElementById('budget');
  const servingsElement = document.getElementById('servings');


  const submitData = {
    avoid:   (avoidElement?.value ?? '').trim(),
    time:     cookElement?.value ? Number(cookElement.value) : null,
    budget:   budgetElement?.value ? Number(budgetElement.value) : null,
    servings: servingsElement?.value ? Number(servingsElement.value) : null,
  };

  try {
    const res = await fetch('/index.php/api/profile.json', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(submitData),
    });

    const data = await res.json();



    // 以下ステータス管理 
    if (data.success) {
      status.textContent = '保存が完了しました';
      setTimeout(() => (status.textContent = ''), 3000);
    } else {
      status.textContent = '保存に失敗：' + (data.error || '不明なエラー');
    }
  } catch (err) {
    status.textContent = 'エラー：' + err.message;
  }
});