<?php

namespace SumoCoders\FrameworkUserBundle\Controller;

use SumoCoders\FrameworkUserBundle\Form\AddUserType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * @Route("/add")
     * @Template()
     */
    public function addAction(Request $request)
    {
        $form = $this->createForm(
            new AddUserType('\SumoCoders\FrameworkUserBundle\Entity\User')
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
}
