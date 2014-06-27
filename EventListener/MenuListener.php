<?php

namespace SumoCoders\FrameworkUserBundle\EventListener;

use SumoCoders\FrameworkCoreBundle\Event\ConfigureMenuEvent;
use SumoCoders\FrameworkCoreBundle\EventListener\DefaultMenuListener;

class MenuListener extends DefaultMenuListener
{
    public function onConfigureMenu(ConfigureMenuEvent $event)
    {
        /** @var \SumoCoders\FrameworkUserBundle\Entity\User $user */
        $user = $this->getSecurityContext()->getToken()->getUser();
        $menu = $event->getMenu();

        if ($user) {
            $menuItem = $event->getFactory()->createItem(
                'menu.users.users',
                array(
                    'uri' => '#',
                    'label' => $user->getUsername(),
                )
            );
            $menuItem->setAttribute('id', 'user');
            $menuItem->setAttribute('icon', 'iconUser');
            $menuItem->setChildrenAttribute('class', 'subNavigation');
            $menuItem->setLinkAttribute('class', 'toggleSubNavigation');
            $menuItem->setExtra('orderNumber', 1);

            $menuItem->addChild(
                'menu.users.settings',
                array(
                    'route' => 'sumocoders_frameworkuser_default_edit',
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

            $menu->addChild($menuItem);
        }
    }
}
