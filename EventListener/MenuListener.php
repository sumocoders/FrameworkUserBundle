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

        if ($this->getSecurityAuthorizationChecker()->isGranted('ROLE_USER')) {
            $menuItem = $event->getFactory()->createItem(
                'user.menu.current_user',
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
                'user.menu.settings',
                array(
                    'route' => 'sumocoders_frameworkuser_user_edit',
                    'routeParameters' => array(
                        'id' => $user->getId(),
                    ),
                )
            );
            $menuItem->addChild(
                'user.menu.logout',
                array(
                    'route' => 'fos_user_security_logout',
                )
            );

            $menu->addChild($menuItem);
        }

        if ($this->getSecurityAuthorizationChecker()->isGranted('ROLE_ADMIN')) {
            $menuItem = $event->getFactory()->createItem(
                'user.menu.users',
                array(
                    'route' => 'sumocoders_frameworkuser_user_index'
                )
            );

            $menu->addChild($menuItem);
        }
    }
}
