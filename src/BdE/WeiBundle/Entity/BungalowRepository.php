<?php

namespace BdE\WeiBundle\Entity;

use Doctrine\ORM\EntityRepository;

class BungalowRepository extends EntityRepository
{
    public function GetAllBungBySexe($sexe,$bung)
    {
    	$qb = $this->createQueryBuilder('b');
        $qb->where('b.nbPlaces > (SELECT COUNT(s) FROM BdEWeiBundle:DetailsWEI s WHERE s.bungalow = b.id)');
        if($bung<>NULL)
        	$qb->orWhere('b.id = :idBung')->setParameter('idBung', $bung->getId());
    	if($sexe!=-1)
    		$qb->andWhere('b.sexe = :sexe')->setParameter('sexe', $sexe);
        return $qb;
    }

    public function getAllNotFull(){
        $qb = $this->createQueryBuilder('b');
        $qb->leftJoin('b.students','e');
        $qb->select("b");
        $qb->having("b.nbPlaces > COUNT(e.id)");
        $qb->groupBy("b.id");
        return $qb->getQuery()->getResult();
    }

    public function getAllNotFullByGender($sex){
        if($sex!=Bungalow::BOYS&&$sex!=Bungalow::GIRLS&&$sex!=Bungalow::NOT_DETERMINED){
            throw new \InvalidArgumentException("Sex should be a valid value from Bungalow::BOYS or Bungalow::GIRLS");
        }
        $qb = $this->createQueryBuilder('b');
        $qb->leftJoin('b.students','e');
        $qb->select('b');
        $qb->where("b.sexe = ?1");
        $qb->orWhere("b.sexe IS NULL");
        $qb->groupBy("b.id");
        $qb->having("b.nbPlaces > COUNT(e.id)");
        $qb->setParameter(1, $sex);
        return $qb->getQuery()->getResult();
    }

    public function countNotFull(){
        return count($this->getAllNotFull());
    }

    public function countAmountOfPlaces(){
        $qb = $this->createQueryBuilder('b');
        $qb->select("SUM(b.nbPlaces)");
        return intval($qb->getQuery()->getSingleScalarResult());
    }

    public function countAmountOfAffectedEtudiant(){
        $qb = $this->createQueryBuilder("b");
        $qb->select("COUNT(e.id)");
        $qb->leftJoin("b.students","e");
        return intval($qb->getQuery()->getSingleScalarResult());
    }

    public function getAllNotFullByGenderAndBus($sex, $bus)
    {
        if($sex!=Bungalow::BOYS&&$sex!=Bungalow::GIRLS&&$sex!=Bungalow::NOT_DETERMINED){
            throw new \InvalidArgumentException("Sex should be a valid value from Bungalow::BOYS or Bungalow::GIRLS");
        }
        $qb = $this->createQueryBuilder('b');
        $qb->leftJoin('b.students','e');
        $qb->select('b');
        $qb->where("b.sexe = ?1 OR b.sexe IS NULL OR b.sexe = ?2");
        $qb->andWhere("b.bus = ?3 OR b.bus IS NULL");
        $qb->groupBy("b.id");
        $qb->having("b.nbPlaces > COUNT(e.id)");
        $qb->setParameter(1, $sex);
        $qb->setParameter(2, Bungalow::NOT_DETERMINED);
        $qb->setParameter(3, $bus);
        return $qb->getQuery()->getResult();
    }
}