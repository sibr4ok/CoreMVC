<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta type="keywords" content="...">
    <meta type="description" content="...">
    <title>Admin panel</title>

    <?php
    foreach ($this->styles as $style): ?>
        <link rel="stylesheet" href="<?= $style ?>">
    <?php endforeach; ?>
</head>

<body>
    <div class="vg-carcass vg-hide">
        <div class="vg-main">
            <div class="vg-one-of-twenty vg-firm-background-color2  vg-center">
                <a href="<?= PATH ?>" target="_blank">
                    <span class="vg-text2 vg-firm-color1">Site</span>
                </a>
            </div>
            <div class="vg-element vg-ninteen-of-twenty vg-firm-background-color4 vg-space-between  vg-box-shadow">
                <div class="vg-element vg-third">
                    <div class="vg-element vg-fifth vg-center" id="hideButton">
                        <div>
                            <img src="<?= PATH . ADMIN_TEMPLATE ?>img/menu-button.png" alt="">
                        </div>
                    </div>
                    <div class="vg-element vg-wrap-size vg-left vg-search  vg-relative" id="searchButton">
                        <div>
                            <img src="<?= PATH . ADMIN_TEMPLATE ?>img/search.png" alt="">
                        </div>
                        <form method="post"
                            action="<?= PATH . core\base\settings\Settings::get('routes')['admin']['alias'] ?>/search"
                            autocomplete="off">
                            <input type="text" name="search" class="vg-input vg-text">
                            <div class="vg-element vg-firm-background-color4 vg-box-shadow search_links search_res">
                            </div>
                        </form>
                    </div>
                </div>
                <a href="<?= PATH . core\base\settings\Settings::get('routes')['admin']['alias'] ?>/createsitemap"
                    class="vg-element vg-box-shadow sitemap-button">
                    <span class="vg-text vg-firm-color1">
                        Create sitemap
                    </span>
                </a>
                <div class="vg-element vg-fifth">
                    <div class="vg-element vg-half vg-right">
                        <div class="vg-element vg-text vg-center">
                            <span class="vg-firm-color5">admin</span>
                        </div>
                    </div>
                    <a href="/login/admin/logout/1" class="vg-element vg-half vg-center">
                        <img src="<?= PATH . ADMIN_TEMPLATE ?>img/out.png" alt="">
                    </a>
                </div>
            </div>
        </div>
        <div class="vg-main vg-right vg-relative">
            <div class="vg-wrap vg-firm-background-color1 vg-center vg-block vg-menu">

                <?php if ($this->menu): ?>
                    <?php foreach ($this->menu as $table => $item): ?>
                        <a href="<?= $this->adminPath ?>show/<?= $table ?>" class="vg-wrap vg-element vg-full vg-center <?php if ($table === $this->table)
                                echo 'active' ?>">
                                <span class="vg-element vg-half  vg-center">
                                    <span>
                                        <img src="<?= PATH . ADMIN_TEMPLATE ?>img/<?= $item['img'] ? $item['img'] : 'pages.png' ?>"
                                        alt="pages">
                                </span>
                            </span>
                            <span class="vg-element vg-half vg-center vg_hidden">
                                <span class="vg-text vg-firm-color5"><?= $item['name'] ? $item['name'] : $table ?></span>
                            </span>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>