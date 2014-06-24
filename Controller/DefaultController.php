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

class DefaultController extends Controller
{
    /**
     * Show an overview of all the users
     *
     * @Route("/overview")
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
     * @Route("/add")
     * @Template()
     *
     * @param Request $request
     * @return array
     */
    public function addAction(Request $request)
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
            $userManager->updateUser($user);

            /** @var \Symfony\Component\HttpFoundation\Session\Session $session */
            $session = $this->get('session');
            /** @var \Symfony\Bundle\FrameworkBundle\Translation\Translator $translator */
            $translator = $this->get('translator');

            $session->getFlashBag()->add(
                'success',
                $translator->trans('user.flash.success.add', array('username' => $user->getUsername()))
            );

            return $this->redirect(
                $this->generateUrl(
                    'sumocoders_frameworkuser_default_index'
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
     * @Route("/edit/{id}", requirements={"id"= "\d+"})
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

        $id = (int) $request->get('id');

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

            $session->getFlashBag()->add(
                'success',
                $translator->trans('user.flash.success.edit', array('username' => $user->getUsername()))
            );

            return $this->redirect(
                $this->generateUrl(
                    'sumocoders_frameworkuser_default_index'
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
     * @Route("/block/{id}", requirements={"id"= "\d+"})
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
     * @Route("/unblock/{id}", requirements={"id"= "\d+"})
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
        $id = (int) $request->get('id');

        // validate our token
        if (!$csrfProvider->isCsrfTokenValid('block_unblock', $token)) {
            $session->getFlashBag()->add(
                'error',
                $translator->trans('forms.errors.invalidToken')
            );

            return $this->redirect(
                $this->generateUrl(
                    'sumocoders_frameworkuser_default_edit',
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
                'sumocoders_frameworkuser_default_index'
            )
        );
    }
}
