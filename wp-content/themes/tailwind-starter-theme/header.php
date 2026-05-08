<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>

    <header class="bg-neutral-100 text-gray-800 p-4">
        <div class="container mx-auto flex justify-between">
            <nav class="flex items-center space-x-4 justify-between w-full">
                <button class="menu-toggle px-2 py-1 rounded border border-gray-300" aria-expanded="false" aria-controls="mobile-menu">Menu</button>
                <aside id="mobile-menu" class="menu ">
                    <?php wp_nav_menu(['theme_location' => 'primary']); ?>
                    <?php if (is_user_logged_in()) {
                        wp_nav_menu(['theme_location' => 'secondary']);
                    } else {
                        echo '<a href="' . wp_login_url() . '">Intranet</a>';
                    } ?>
                </aside>

                <div>
                    <?= has_custom_logo() ? get_custom_logo() : ''; ?>
                </div>

                <form class="max-w-md mx-auto">
                    <label for="search" class="block mb-2.5 text-sm font-medium text-heading sr-only ">Search</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                            <svg class="w-4 h-4 text-body" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="m21 21-3.5-3.5M17 10a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                            </svg>
                        </div>
                        <input type="search" id="search" class="block w-full p-3 ps-9 bg-neutral-secondary-medium border border-default-medium text-heading text-sm rounded-base focus:ring-brand focus:border-brand shadow-xs placeholder:text-body" placeholder="Search" required />
                        <button type="button" class="absolute end-1.5 bottom-1.5 text-white bg-brand hover:bg-brand-strong box-border border border-transparent focus:ring-4 focus:ring-brand-medium shadow-xs font-medium leading-5 rounded text-xs px-3 py-1.5 focus:outline-none">Search</button>
                    </div>
                </form>

            </nav>
        </div>
    </header>