<?php

namespace SumoCoders\FrameworkUserBundle\EventListener;

use SumoCoders\FrameworkCoreBundle\Event\ConfigureMenuEvent;
use SumoCoders\FrameworkCoreBundle\EventListener\DefaultMenuListener;

class MenuListener extends DefaultMenuListener
{
    public function onConfigureMenu(ConfigureMenuEvent $event)
    {
        $menu = $event->getMenu();

        if ($this->getSecurityAuthorizationChecker()->isGranted('ROLE_ADMIN')) {
            $menuItem = $event->getFactory()->createItem(
                'user.menu.users',
                array(
                    'route' => 'sumocoders_frameworkuser_user_index'
                )
            );
            $menuItem->setLinkAttributes(
                array(
                    'class' => 'menu-item',
                )
            );

            $menu->addChild($menuItem);
        }
    }
}
