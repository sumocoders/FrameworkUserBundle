<?php

namespace SumoCoders\FrameworkUserBundle\EventListener;

use SumoCoders\FrameworkSearchBundle\Entity\SearchResult;
use SumoCoders\FrameworkSearchBundle\Event\SearchEvent;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SearchListener implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->setContainer($container);
    }

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function onSearch(SearchEvent $event)
    {
        $ids = $event->getFoundIdsForClass('SumoCoders\FrameworkUserBundle\Entity\User');

        /** @var $userManager \SumoCoders\FrameworkUserBundle\Model\FrameworkUserManager */
        $userManager = $this->container->get('fos_user.user_manager');
        $users = $userManager->findActiveByIds($ids);

        if(!empty($users))
        {
            foreach($users as $user)
            {
                /** @var $user \SumoCoders\FrameworkUserBundle\Entity\User */

                // @todo    use correct route
                $result = new SearchResult(
                    'SumoCoders\FrameworkUserBundle\Entity\User',
                    $user->getId(),
                    'users',
                    $user->getUsername(),
                    'route'
                );

                $event->addResult($result);
            }
        }
    }
}