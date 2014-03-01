<?php

namespace SumoCoders\FrameworkUserBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class SumoCodersFrameworkUserBundle extends Bundle
{
    /**
     * Override the getParent()-method so we can overrule the templates inside
     * FOSUserBundle with our own
     *
     * @return string
     */
    public function getParent()
    {
        return 'FOSUserBundle';
    }
}
