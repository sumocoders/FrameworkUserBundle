<?php

namespace SumoCoders\FrameworkUserBundle\Controller;

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

        $user->setEnabled(false);
        $userManager->updateUser($user);

        $session->getFlashBag()->add(
            'success',
            $translator->trans('user.flash.success.blocked', array('entity' => $user->getUsername()))
        );

        return $this->redirect(
            $this->generateUrl(
                'sumocoders_frameworkuser_default_index'
            )
        );
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

        $user->setEnabled(true);
        $userManager->updateUser($user);

        $session->getFlashBag()->add(
            'success',
            $translator->trans('user.flash.success.unblocked', array('entity' => $user->getUsername()))
        );

        return $this->redirect(
            $this->generateUrl(
                'sumocoders_frameworkuser_default_index'
            )
        );
    }
}
