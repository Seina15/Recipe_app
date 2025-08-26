<link rel="stylesheet" href="/assets/css/shopping-list.css">

<ul class="shopping-list">
    <?php foreach ($items as $item): ?>
        <li>
            <input type="checkbox" id="item<?= $item ?>">
            <label for="item<?= $item ?>"><?= $item ?></label>
        </li>
    <?php endforeach; ?>
</ul>
