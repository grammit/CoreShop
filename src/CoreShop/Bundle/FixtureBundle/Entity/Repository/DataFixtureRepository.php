<?php

namespace CoreShop\Bundle\FixtureBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use CoreShop\Bundle\FixtureBundle\Entity\DataFixture;

class DataFixtureRepository extends EntityRepository
{
    /**
     * @param $className
     *
     * @return DataFixture[]
     */
    public function findByClassName($className): array
    {
        return $this->findBy(['className' => $className]);
    }

    /**
     * @param string $where
     * @param array  $parameters
     *
     * @return bool
     */
    public function isDataFixtureExists($where, array $parameters = []): bool
    {
        $entityId = $this->createQueryBuilder('m')
            ->select('m.id')
            ->where($where)
            ->setMaxResults(1)
            ->getQuery()
            ->execute($parameters);

        return $entityId ? true : false;
    }

    /**
     * Update data fixture history
     *
     * @param array  $updateFields assoc array with field names and values that should be updated
     * @param string $where        condition
     * @param array  $parameters   optional parameters for where condition
     */
    public function updateDataFixtureHistory(array $updateFields, $where, array $parameters = []): void
    {
        $qb = $this->_em
            ->createQueryBuilder()
            ->update('CoreShopFixtureBundle:DataFixture', 'm')
            ->where($where);

        foreach ($updateFields as $fieldName => $fieldValue) {
            $qb->set($fieldName, $fieldValue);
        }
        $qb->getQuery()->execute($parameters);
    }
}
