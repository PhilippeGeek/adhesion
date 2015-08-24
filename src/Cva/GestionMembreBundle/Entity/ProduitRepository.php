<?php

namespace Cva\GestionMembreBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;

/**
 * ProduitRepository
 *
 * This class was generated by the PhpStorm "Php Annotations" Plugin. Add your own custom
 * repository methods below.
 */
class ProduitRepository extends EntityRepository
{

    public function getAvailableProductsFor(Etudiant $student){
        // Select only product which has not be bought by this student
        $boughtProducts = array();
        /** @var Payment[] $payments */
        $payments = $student->getPayments();
        foreach($payments as $payment){
            $boughtProducts[]=$payment->getProduct()->getId();
        }
        // The query to achieve what we are looking for
        $qb = $this->createQueryBuilder('p')->where("p.active = true");
        if(count($boughtProducts)>0){ // This request bug if $boughtProducts is empty
            $qb->andWhere("p.id NOT IN (?2)")->setParameter(2,$boughtProducts);
        }
        return $this->to_array_result($qb->getQuery()->getResult(Query::HYDRATE_OBJECT));
    }

    public function getCurrentVA(){
        $result = $this->createQueryBuilder('p')->where("p.id IN (?1)")
            ->set(1, $this->getCurrentVAIds())->getQuery()->getResult();
        return $this->to_array_result($result);
    }

    public function getCurrentWEI(){
        return $this->find($this->getCurrentWEIIds());
    }

    public function getCurrentWEIRemboursement(){
        return $this->find($this->getCurrentWEIRemboursementIds());
    }

    public function getCurrentWEIPreInscription(){
        return $this->find($this->getCurrentWEIIds());
    }


    public function getCurrentVAIds(){
        $config = $this->getEntityManager()->getRepository("BdEMainBundle:Config")->get("cva.produitVA","");
        return explode(",",$config);
    }

    public function getCurrentWEIIds(){
        return $this->getEntityManager()->getRepository("BdEMainBundle:Config")->get("produitInscriptionWEI","");
    }

    public function getCurrentWEIRemboursementIds(){
        return $this->getEntityManager()->getRepository("BdEMainBundle:Config")->get("produitRemboursementWEI","");
    }

    /**
     * @param $result
     * @return array
     */
    private function to_array_result($result)
    {
        if (!$result) {
            return array();
        } elseif (is_array($result)) {
            return $result;
        } else {
            return array($result);
        }
    }

    public function getAvailableProducts()
    {
        $qb = $this->createQueryBuilder('p')->where("p.active = true");
        return $this->to_array_result($qb->getQuery()->getResult(Query::HYDRATE_OBJECT));
    }

}
