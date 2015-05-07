<?php

namespace SumoCoders\FrameworkUserBundle\EventListener;

use SumoCoders\FrameworkCoreBundle\Event\ConfigureMenuEvent;
use SumoCoders\FrameworkCoreBundle\EventListener\DefaultMenuListener;

class MenuListener extends DefaultMenuListener
{
    public function onConfigureMenu(ConfigureMenuEvent $event)
    {
        /** @var \SumoCoders\FrameworkUserBundle\Entity\User $user */
        $user = $this->getSecurityTokenStorage()->getToken()->getUser();
        $menu = $event->getMenu();

        if ($user) {
            $menuItem = $event->getFactory()->createItem(
                'menu.users.user',
                array(
                    'uri' => '#',
                    'label' => $user->getUsername(),
                )
            );
            $menuItem->setAttribute('class', 'dropdown');
            $menuItem->setAttribute('icon', 'icon icon-angle');
            $menuItem->setChildrenAttribute('class', 'dropdown-menu');
            $menuItem->setChildrenAttribute('role', 'menu');
            $menuItem->setLinkAttribute('class', 'menu-item dropdown-toggle');
            $menuItem->setLinkAttribute('role', 'button');
            $menuItem->setLinkAttribute('aria-expanded', 'false');
            $menuItem->setExtra('orderNumber', 1);

            $menuItem->addChild(
                'menu.users.settings',
                array(
                    'route' => 'sumocoders_frameworkuser_user_edit',
                    'routeParameters' => array(
                        'id' => $user->getId(),
                    ),
                )
            );
            $menuItem->addChild(
                'menu.users.logout',
                array(
                    'route' => 'fos_user_security_logout',
                )
            );

            // add the sub-menu-item class to all sub-menu-items
            foreach ($menuItem as $child) {
                $child->setLinkAttribute('class', 'sub-menu-item');
            }

            $menu->addChild($menuItem);

            if ($user->hasRole('ROLE_SUPER_ADMIN')) {
                $menuItem = $event->getFactory()->createItem(
                    'menu.users.users',
                    array(
                        'route' => 'sumocoders_frameworkuser_user_index'
                    )
                );

                $menu->addChild($menuItem);
            }
        }
    }
}
