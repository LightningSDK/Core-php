<?php

namespace lightningsdk\core\View;

use lightningsdk\core\Tools\ClientUser;
use lightningsdk\core\Tools\Configuration;
use lightningsdk\core\Tools\Scrub;

class Menu {
    /**
     * Renders an array to a menu of UL / LI elements in the following format:
     *
     * [
     *     'Menu Item Label 1' => [ // object
     *         url: "/url",
     *         onclick: "function()",
     *         children: [], // list of submenu items
     *     ],
     *     'Menu Item Label 2' => '/url'
     * ]
     *
     * @param $data
     * @param $appendSessionControl
     * @return string
     */
    public static function render($menu, $appendSessionControl = false) {
        // Load the menu from the  config.
        $data = Configuration::get('menus.' . $menu, []);

        // Append the user session control
        if ($appendSessionControl) {
            if (ClientUser::getInstance()->isImpersonating()) {
                $data['Return to Admin User'] = '/user?action=stop-impersonating';
            } else if (ClientUser::getInstance()->id > 0)  {
                $data['Sign Out'] = '/user?action=logout';
            } else {
                $data['Sign In'] = '/user';
            }
        }

        return static::renderElements($data);
    }

    protected static function renderElements($data) {
        $output = '';
        foreach ($data as $label => $item) {
            $menu_contact = strtolower(Scrub::url($label));
            if ($menu_contact == 'contact-us') {
                $menu_contact = 'contact';
            }
            if (!is_array($item)) {
                $output .= '<li class="' . $menu_contact . '"><a href="' . $item . '">' . $label . '</a></li>';
            }
            else {
                $has_children = !empty($item['children']) || (is_object($item) && empty($item['url']) && empty($item['onclick']));
                $classes = !empty($item['class']) ? $item['class'] : '';
                if ($has_children) {
                    $classes .= ' is-dropdown-submenu-parent ';
                }

                // Add the menu context;
                $classes .= ' ' . $menu_contact;

                $output .= '<li';
                if (!empty($classes)) {
                    $output .= ' class="' . $classes . '"';
                }
                $output .= '><a href="' . (!empty($item['url']) ? $item->url : '#') . '"';

                if (!empty($item['onclick'])) {
                    $output .= ' onclick="' . $item['onclick'] . '" ';
                }

                $output .= '>' . $label . '</a>';

                if (!empty($item['children'])) {
                    $output .= '<ul class="menu is-dropdown-submenu-parent">' . static::renderElements($item['children']) . '</ul>';
                } elseif ($has_children) {
                    $output .= '<ul class="menu">' . self::render($item) . '</ul>';
                }
                $output .= '</li>';
            }
        }
        return $output;
    }
}
