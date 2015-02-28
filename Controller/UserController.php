<?php

namespace SumoCoders\FrameworkUserBundle\Controller;

use SumoCoders\FrameworkUserBundle\Form\OtherUserType;
use SumoCoders\FrameworkUserBundle\Form\OwnUserType;
use SumoCoders\FrameworkUserBundle\Form\UserType;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class UserController extends Controller
{
    /**
     * Show an overview of all the users
     *
     * @Route("/")
     * @Template()
     */
    public function indexAction()
    {
        /** @var $userManager \SumoCoders\FrameworkUserBundle\Model\FrameworkUserManager */
        $userManager = $this->container->get('fos_user.user_manager');
        $users = $userManager->findUsers();

        /** @var $paginator \Knp\Component\Pager\Paginator */
        $paginator = $this->get('knp_paginator');
        $paginatedUsers = $paginator->paginate(
            $users,
            $this->get('request')->query->get('page', 1)
        );

        return array(
            'dgUsers' => $paginatedUsers,
        );
    }

    /**
     * Add a user
     *
     * @Route("/new")
     * @Template()
     *
     * @param Request $request
     * @return array
     */
    public function newAction(Request $request)
    {
        $form = $this->createForm(
            new UserType('\SumoCoders\FrameworkUserBundle\Entity\User')
        );

        $form->handleRequest($request);

        if ($form->isValid()) {
            /** @var \SumoCoders\FrameworkUserBundle\Model\FrameworkUserManager $userManager */
            $userManager = $this->container->get('fos_user.user_manager');
            /** @var \SumoCoders\FrameworkUserBundle\Entity\User $user */
            $user = $form->getData();
            $user->setEnabled(true);
            $userManager->updateUser($user);

            /** @var \Symfony\Component\HttpFoundation\Session\Session $session */
            $session = $this->get('session');
            /** @var \Symfony\Bundle\FrameworkBundle\Translation\Translator $translator */
            $translator = $this->get('translator');

            $session->getFlashBag()->add(
                'success',
                $translator->trans('user.flash.success.add', array('username' => $user->getUsername()))
            );

            if (array_key_exists(
                'SumoCodersFrameworkSearchBundle',
                $this->container->getParameter('kernel.bundles')
            )
            ) {
                $searchIndexItems = \SumoCoders\FrameworkSearchBundle\Entity\IndexItem::createMultipleObjectsBasedOnProperties(
                    'SumoCoders\FrameworkUserBundle\Entity\User',
                    $user->getId(),
                    array('username', 'email'),
                    $user
                );

                $event = new \SumoCoders\FrameworkSearchBundle\Event\IndexUpdateEvent();
                $event->setObjects($searchIndexItems);
                $this->get('event_dispatcher')->dispatch('framework_search.index_update', $event);
            }

            return $this->redirect(
                $this->generateUrl(
                    'sumocoders_frameworkuser_user_index'
                )
            );
        }

        return array(
            'form' => $form->createView(),
        );
    }

    /**
     * Edit a user
     *
     * @Route("/{id}/edit", requirements={"id"= "\d+"})
     * @Template()
     *
     * @param Request $request
     * @return array
     */
    public function editAction(Request $request)
    {
        /** @var \Symfony\Component\Form\Extension\Csrf\CsrfProvider\CsrfTokenManagerAdapter $csrfProvider */
        $csrfProvider = $this->get('form.csrf_provider');
        /** @var \Symfony\Component\HttpFoundation\Session\Session $session */
        $session = $this->get('session');
        /** @var \Symfony\Bundle\FrameworkBundle\Translation\Translator $translator */
        $translator = $this->get('translator');

        $id = (string) $request->get('id');

        /** @var \SumoCoders\FrameworkUserBundle\Model\FrameworkUserManager $userManager */
        $userManager = $this->container->get('fos_user.user_manager');
        /** @var \SumoCoders\FrameworkUserBundle\Entity\User $user */
        $user = $userManager->findUserBy(array('id' => $id));
        /** @var \SumoCoders\FrameworkUserBundle\Entity\User $currentUser */
        $currentUser = $this->get('security.context')->getToken()->getUser();

        // validate the user
        if (!$user) {
            throw new NotFoundHttpException(
                $translator->trans('core.errors.notFound')
            );
        }

        // if the current user is editing itself it should see the password field
        if ($currentUser->getId() == $user->getId()) {
            $type = new OwnUserType('\SumoCoders\FrameworkUserBundle\Entity\User');
        } else {
            $type = new OtherUserType('\SumoCoders\FrameworkUserBundle\Entity\User');
        }

        $form = $this->createForm($type, $user);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $user = $form->getData();
            $userManager->updateUser($user);

            if (array_key_exists(
                'SumoCodersFrameworkSearchBundle',
                $this->container->getParameter('kernel.bundles')
            )
            ) {
                $searchIndexItems = \SumoCoders\FrameworkSearchBundle\Entity\IndexItem::createMultipleObjectsBasedOnProperties(
                    'SumoCoders\FrameworkUserBundle\Entity\User',
                    $user->getId(),
                    array('username', 'email'),
                    $user
                );

                $event = new \SumoCoders\FrameworkSearchBundle\Event\IndexUpdateEvent();
                $event->setObjects($searchIndexItems);
                $this->get('event_dispatcher')->dispatch('framework_search.index_update', $event);
            }

            $session->getFlashBag()->add(
                'success',
                $translator->trans('user.flash.success.edit', array('username' => $user->getUsername()))
            );

            return $this->redirect(
                $this->generateUrl(
                    'sumocoders_frameworkuser_user_index'
                )
            );
        }

        return array(
            'form' => $form->createView(),
            'token' => $csrfProvider->generateCsrfToken('block_unblock'),
            'user' => $user,
        );
    }

    /**
     * Block a user
     *
     * We won't delete users, as users can/will be linked through other stuff
     * in our application.
     *
     * @Route("/{id}/block", requirements={"id"= "\d+"})
     * @Method({"POST"})
     * @Template()
     *
     * @param Request $request
     * @return array
     */
    public function blockAction(Request $request)
    {
        return $this->handleBlockUnBlock('block', $request);
    }

    /**
     * Unblock a user
     *
     * @Route("/{id}/unblock", requirements={"id"= "\d+"})
     * @Method({"POST"})
     * @Template()
     *
     * @param Request $request
     * @return array
     */
    public function unblockAction(Request $request)
    {
        return $this->handleBlockUnBlock('unblock', $request);
    }

    /**
     * @param string  $type
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    private function handleBlockUnBlock($type, Request $request)
    {
        /** @var \Symfony\Component\Form\Extension\Csrf\CsrfProvider\CsrfTokenManagerAdapter $csrfProvider */
        $csrfProvider = $this->get('form.csrf_provider');
        /** @var \Symfony\Component\HttpFoundation\Session\Session $session */
        $session = $this->get('session');
        /** @var \Symfony\Bundle\FrameworkBundle\Translation\Translator $translator */
        $translator = $this->get('translator');

        $token = $request->get('token');
        $id = $request->get('id');

        // validate our token
        if (!$csrfProvider->isCsrfTokenValid('block_unblock', $token)) {
            $session->getFlashBag()->add(
                'error',
                $translator->trans('forms.errors.invalidToken')
            );

            return $this->redirect(
                $this->generateUrl(
                    'sumocoders_frameworkuser_user_edit',
                    array('id' => $id)
                )
            );
        }

        /** @var \SumoCoders\FrameworkUserBundle\Model\FrameworkUserManager $userManager */
        $userManager = $this->container->get('fos_user.user_manager');
        /** @var \SumoCoders\FrameworkUserBundle\Entity\User $user */
        $user = $userManager->findUserBy(array('id' => $id));

        // validate the user
        if (!$user) {
            throw new NotFoundHttpException(
                $translator->trans('core.errors.notFound')
            );
        }

        if ($type == 'unblock') {
            $enabled = true;
            $message = 'user.flash.success.unblocked';
        } else {
            $enabled = false;
            $message = 'user.flash.success.blocked';
        }

        $user->setEnabled($enabled);
        $userManager->updateUser($user);

        $session->getFlashBag()->add(
            'success',
            $translator->trans($message, array('entity' => $user->getUsername()))
        );

        return $this->redirect(
            $this->generateUrl(
                'sumocoders_frameworkuser_user_index'
            )
        );
    }
}
