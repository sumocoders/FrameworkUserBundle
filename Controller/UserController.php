<?php

namespace SumoCoders\FrameworkUserBundle\Controller;

use SumoCoders\FrameworkUserBundle\Entity\User;
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
     * @Method({"GET"})
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
     * @Method({"GET|POST"})
     * @Template()
     *
     * @param Request $request
     * @return array
     */
    public function newAction(Request $request)
    {
        // fix the breadCrumb
        $this->get('framework.breadcrumb_builder')
            ->extractItemsBasedOnUri(
                $this->generateUrl('sumocoders_frameworkuser_user_index'),
                $request->getLocale()
            )
            ->addSimpleItem(
                'user.breadcrumb.new',
                $this->generateUrl('sumocoders_frameworkuser_user_new')
            );

        $form = $this->createForm(
            new UserType('\SumoCoders\FrameworkUserBundle\Entity\User')
        );
        $form->add('roles', 'choice', array(
            'choices' => $this->getExistingRoles(),
            'data' => array(),
            'label' => 'Roles',
            'expanded' => true,
            'multiple' => true,
            'mapped' => true,
        ));

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

            $this->addFlash(
                'success',
                $translator->trans('user.flash.success.add', array('%username%' => $user->getUsername()))
            );

            // @todo move this in an event!
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
     * @Method({"GET|POST"})
     * @Template()
     *
     * @param Request $request
     * @param int     $id
     * @return array
     */
    public function editAction(Request $request, $id)
    {
        // fix the breadCrumb
        $this->get('framework.breadcrumb_builder')
            ->extractItemsBasedOnUri(
                $this->generateUrl('sumocoders_frameworkuser_user_index'),
                $request->getLocale()
            )
            ->addSimpleItem(
                'user.breadcrumb.edit',
                $this->generateUrl(
                    'sumocoders_frameworkuser_user_edit',
                    array('id' => $id)
                )
            );

        /** @var \Symfony\Component\Form\Extension\Csrf\CsrfProvider\CsrfTokenManagerAdapter $csrfProvider */
        $csrfProvider = $this->get('form.csrf_provider');
        /** @var \Symfony\Component\HttpFoundation\Session\Session $session */
        $session = $this->get('session');
        /** @var \Symfony\Bundle\FrameworkBundle\Translation\Translator $translator */
        $translator = $this->get('translator');

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
        $form->add(
            'roles',
            'choice',
            array(
                'choices' => $this->getExistingRoles(),
                'data' => $user->getRoles(),
                'label' => 'Roles',
                'expanded' => true,
                'multiple' => true,
            )
        );

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
                $translator->trans('user.flash.success.edit', array('%username%' => $user->getUsername()))
            );

            return $this->redirect(
                $this->generateUrl(
                    'sumocoders_frameworkuser_user_index'
                )
            );
        }

        if ($user->isEnabled()) {
            $blockUnblockForm = $this->createBlockUnblockForm($user, 'block');
        } else {
            $blockUnblockForm = $this->createBlockUnblockForm($user, 'unblock');
        }

        return array(
            'form' => $form->createView(),
            'token' => $csrfProvider->generateCsrfToken('block_unblock'),
            'user' => $user,
            'form_block_unblock' => $blockUnblockForm->createView(),
        );
    }

    /**
     * Creates a form to block a user.
     *
     * @param User $user The entity
     * @return \Symfony\Component\Form\Form The form
     */
    private function createBlockUnblockForm(User $user, $action = 'block')
    {
        $allowedActions = array(
            'block',
            'unblock',
        );

        if (!in_array($action, $allowedActions)) {
            throw new \InvalidArgumentException(
                'Invalid action, possible values are: ' . implode(', ', $allowedActions)
            );
        }

        if ('block' === $action) {
            $route = 'sumocoders_frameworkuser_user_block';
            $label = 'user.forms.buttons.block';
            $message = 'user.dialogs.messages.confirmBlock';
            $class = 'btn-danger';
            $icon = 'fa fa-remove';
        }
        if ('unblock' === $action) {
            $route = 'sumocoders_frameworkuser_user_unblock';
            $label = 'user.forms.buttons.unblock';
            $message = 'user.dialogs.messages.confirmUnblock';
            $class = 'btn-success';
            $icon = 'fa fa-check';
        }

        return $this->createFormBuilder()
            ->setAction(
                $this->generateUrl(
                    $route,
                    array(
                        'id' => $user->getId(),
                    )
                )
            )
            ->setMethod('POST')
            ->add(
                'submit',
                'submit',
                array(
                    'icon' => $icon,
                    'label' => ucfirst($this->get('translator')->trans($label)),
                    'attr' => array(
                        'class' => 'confirm ' . $class,
                        'data-message' => $this->get('translator')->trans(
                            $message,
                            array(
                                '%entity%' => $user,
                            )
                        ),
                    ),
                )
            )
            ->getForm();
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
     * @param User $user
     * @return array
     */
    public function blockAction(User $user)
    {
        return $this->handleBlockUnBlock('block', $user);
    }

    /**
     * Unblock a user
     *
     * @Route("/{id}/unblock", requirements={"id"= "\d+"})
     * @Method({"POST"})
     * @Template()
     *
     * @param User $user
     * @return array
     */
    public function unblockAction(User $user)
    {
        return $this->handleBlockUnBlock('unblock', $user);
    }

    /**
     * @param string $type
     * @param User   $user
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    private function handleBlockUnBlock($type, User $user)
    {
        /** @var \Symfony\Bundle\FrameworkBundle\Translation\Translator $translator */
        $translator = $this->get('translator');

        if ($type == 'unblock') {
            $enabled = true;
            $message = 'user.flash.success.unblocked';
        } else {
            $enabled = false;
            $message = 'user.flash.success.blocked';
        }

        $user->setEnabled($enabled);
        $this->container->get('fos_user.user_manager')->updateUser($user);

        $this->addFlash(
            'success',
            $translator->trans($message, array('%username%' => $user->getUsername()))
        );

        return $this->redirect(
            $this->generateUrl(
                'sumocoders_frameworkuser_user_edit',
                array(
                    'id' => $user->getId(),
                )
            )
        );
    }

    /**
     * Fetches all possible roles stated in our role_hierarchy setting
     *
     * @return array
     */
    protected function getExistingRoles()
    {
        $roleHierarchy = $this->container->getParameter('security.role_hierarchy.roles');
        $roles = array_keys($roleHierarchy);

        $cleanedUpRoles = array();
        foreach ($roles as $role) {
            $cleanedUpRoles[$role] = $role;
        }

        return $cleanedUpRoles;
    }
}
