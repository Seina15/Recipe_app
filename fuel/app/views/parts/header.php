<link rel="stylesheet" href="/assets/css/header_style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=M+PLUS+Rounded+1c:wght@400;700&display=swap" rel="stylesheet">

<header>
    <h3 class="account-name">Hello <?php if (isset($username)) { echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); } ?> !</h3>
    <h1 class="header-title">MyCookList</h1>
    <?php
        $profileUrl = '/home/profile';
        if (isset($profile_url) && preg_match('/^https?:\/\//', $profile_url)) {
            $profileUrl = $profile_url;
        }
    ?>
    <a id="profile-icon" href="<?= htmlspecialchars($profileUrl, ENT_QUOTES, 'UTF-8') ?>" title="プロフィール" class="profile-icon-link">
    <i class="fa-solid fa-user"></i>
    </a>
</header>
