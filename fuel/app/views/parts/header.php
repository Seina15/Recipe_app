<link rel="stylesheet" href="/assets/css/header_style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=M+PLUS+Rounded+1c:wght@400;700&display=swap" rel="stylesheet">

<header>
    <h3 class="account-name">Hello <?php if (isset($username)) { echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); } ?> !</h3>
    <h1 class="header-title">MyCookList</h1>
    <a id="profile-icon" href="/home/profile" title="プロフィール" style="margin-left:20px;">
    <i class="fa-solid fa-user"></i>
    </a>
</header>
