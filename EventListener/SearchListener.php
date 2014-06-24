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

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * Will be executed when the search.search-event is triggered
     *
     * @param SearchEvent $event
     */
    public function onSearch(SearchEvent $event)
    {
        $ids = $event->getFoundIdsForClass('SumoCoders\FrameworkUserBundle\Entity\User');

        /** @var \Symfony\Bundle\FrameworkBundle\Routing\Router $router */
        $router = $this->container->get('router');
        /** @var $userManager \SumoCoders\FrameworkUserBundle\Model\FrameworkUserManager */
        $userManager = $this->container->get('fos_user.user_manager');
        $users = $userManager->findActiveByIds($ids);

        if (!empty($users)) {
            foreach ($users as $user) {
                /** @var $user \SumoCoders\FrameworkUserBundle\Entity\User */
                $result = new SearchResult(
                    'SumoCoders\FrameworkUserBundle\Entity\User',
                    $user->getId(),
                    'users',
                    $user->getUsername(),
                    $router->generate(
                        'sumocoders_frameworkuser_default_edit',
                        array('id' => $user->getId())
                    )
                );

                $event->addResult($result);
            }
        }
    }
}
