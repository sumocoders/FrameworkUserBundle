<?php

namespace SumoCoders\FrameworkUserBundle\Model;

use FOS\UserBundle\Doctrine\UserManager;

class FrameworkUserManager extends UserManager
{
    public function findActiveByIds(array $ids)
    {
        $query = $this->repository->createQueryBuilder('u')
            ->where('u.id IN(:ids) AND u.enabled = true')
            ->setParameter('ids', $ids)
            ->getQuery();

        $users = $query->getResult();

        return $users;
    }
}
