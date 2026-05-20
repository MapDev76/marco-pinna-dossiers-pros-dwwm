<?php $route = $route ?? ($_GET['route'] ?? 'home'); ?>
<header class="header">
    <nav class="topnav" aria-label="Main navigation">
        <div class="topnav-inner">
            <div class="nav-left">
                <?php if ($route === 'dashboard'): ?>
                    <div class="icon-group" aria-label="Quick actions left">
                        <a href="#" class="icon-btn" aria-label="Document">
                            <img src="/assets/icons/document.svg" alt="" class="nav-icon">
                        </a>
                        <a href="#" class="icon-btn" aria-label="Print">
                            <img src="/assets/icons/print-outline.svg" alt="" class="nav-icon">
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <div class="nav-center">
                <a href="/?route=home" class="logo-link" aria-label="StaffEase Pro Home">
                    <img src="/assets/images/LogoStaffeasePro.jpg" alt="StaffEase Pro" class="logo">
                </a>
            </div>

            <div class="nav-right">
                <div class="icon-group" aria-label="Quick actions right">
                    <?php if ($route === 'dashboard'): ?>
                        <a href="/?route=home" class="icon-btn" aria-label="Home">
                            <img src="/assets/icons/home.svg" alt="" class="nav-icon">
                        </a>
                        <a href="#" class="icon-btn" aria-label="Settings">
                            <img src="/assets/icons/setting.svg" alt="" class="nav-icon">
                        </a>
                        <a href="#" class="icon-btn" aria-label="Language">
                            <img src="/assets/icons/language.svg" alt="" class="nav-icon">
                        </a>
                        <a href="#" class="icon-btn" aria-label="Light mode">
                            <img src="/assets/icons/light.svg" alt="" class="nav-icon">
                        </a>
                    <?php elseif ($route === 'home'): ?>
                        <a href="/?route=login" class="icon-btn" aria-label="Login">
                            <img src="/assets/icons/log-in.svg" alt="" class="nav-icon">
                        </a>
                    <?php else: ?>
                        <a href="/?route=home" class="icon-btn" aria-label="Back to home">
                            <img src="/assets/icons/home.svg" alt="" class="nav-icon">
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
</header>