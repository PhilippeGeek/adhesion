<?php

namespace BdE\WeiBundle\Controller;

use BdE\WeiBundle\Entity\Bungalow;
use BdE\WeiBundle\Form\BungalowType;
use Cva\GestionMembreBundle\Entity\Etudiant;
use Doctrine\ORM\NoResultException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class BungalowController extends Controller
{

    /**
     * @Route("/",name="bde_wei_bungalow")
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(){
        // Load data
        $em = $this->getDoctrine()->getManager();
        $bungalows = $em->getRepository("BdEWeiBundle:Bungalow")->findAll();
        return $this->render("@BdEWei/Bungalow/index.html.twig",array('bungalows'=>$bungalows));
    }

    /**
    * @Route("/assign",name="bde_wei_bungalow_assign")
    * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
    */
    public function assignAction(){
        $em = $this->getDoctrine()->getManager();
        $weiProduct = $em->getRepository("CvaGestionMembreBundle:Produit")->getCurrentWEI();
        $students = $em->getRepository("CvaGestionMembreBundle:Etudiant")->createQueryBuilder('etudiant')
            ->leftJoin('etudiant.payments','payment')->where('payment.product = ?1')
            ->setParameter(1, $weiProduct)->getQuery()->getResult();
        $bungalowsWithBus = $em->getRepository("BdEWeiBundle:Bungalow")->createQueryBuilder('bungalow')
            ->where('bungalow.bus IS NOT NULL')
            ->having('bungalow.nbPlaces > COUNT(etudiants.id)')
            ->leftJoin('bungalow.students','etudiants')
            ->groupBy('bungalow.id')
            ->getQuery()->getResult();
        $bungalowsWithoutBus = $em->getRepository("BdEWeiBundle:Bungalow")->createQueryBuilder('bungalow')
            ->where('bungalow.bus IS NULL')
            ->having('bungalow.nbPlaces > COUNT(etudiants.id)')
            ->leftJoin('bungalow.students','etudiants')
            ->groupBy('bungalow.id')
            ->getQuery()->getResult();
        $fuller = array();
        /** @var Bungalow $bungalow */
        foreach(array_merge($bungalowsWithBus,$bungalowsWithoutBus) as $bungalow){
            $fuller[$bungalow->getId()] = $bungalow->getAmountOfRegisteredEtudiants();
        }
        /** @var Etudiant $student */
        foreach ($students as $student) {
            if($student->getBungalow())
                break;
            /** @var Bungalow $bungalow */
            foreach($bungalowsWithBus as $key => $bungalow){

                if($fuller[$bungalow->getId()] >= $bungalow->getNbPlaces()) {
                    unset($bungalowsWithBus[$key]);
                    continue;
                }

                if($bungalow->getSexe() == Bungalow::NOT_DETERMINED OR $bungalow->getSexe() == ($student->getCivilite()=='M'?Bungalow::BOYS:Bungalow::GIRLS)){
                    if($bungalow->getBus() == $student->getBus()){
                        $fuller[$bungalow->getId()]++;
                        $student->setBungalow($bungalow);
                        $bungalow->addStudent($student);
                        break;
                    }
                }
            }
            if($student->getBungalow() == null){
                foreach($bungalowsWithoutBus as $key => $bungalow){

                    if($fuller[$bungalow->getId()] >=  $bungalow->getNbPlaces()) {
                        unset($bungalowsWithoutBus[$key]);
                        continue;
                    }

                    if($bungalow->getSexe() == Bungalow::NOT_DETERMINED OR $bungalow->getSexe() == ($student->getCivilite()=='M'?Bungalow::BOYS:Bungalow::GIRLS)){
                        $fuller[$bungalow->getId()]++;
                        $student->setBungalow($bungalow);
                        $bungalow->addStudent($student);
                        break;
                    }
                }
            }
            $em->persist($student);
        }
        $em->flush();
        return $this->redirectToRoute("bde_wei_bungalow");
    }

    /**
    * @Route("/unassign",name="bde_wei_bungalow_unassign")
    * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
    */
    public function unassignAction(){
        // Load data
        $em = $this->getDoctrine()->getManager();
        $bungalows = $em->getRepository("BdEWeiBundle:Bungalow")->findAll();
        foreach ($bungalows as $bungalow) {
            /** @var Etudiant $student */
            foreach($bungalow->getStudents() as $student){
                $student->setBungalow(null);
            }
            $em->persist($bungalow);
        }
        $em->flush();
        $this->addFlash('success', "Les étudiants ont été viré des bungalow !");
        return $this->redirectToRoute("bde_wei_bungalow");
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @Route(path="/addMany",name="bde_wei_bungalow_add_many")
     * @Method({"GET","POST"})
     */
    public function addManyAction(Request $request)
    {
        // Get data from Form
        // The way to use a get from the request is bad but on one side you can't use symfony form on GET request
        // And on the other way you can't put it as url param because it comes from a form and JS is bad !
        $amount = $this->getRequest()->get("nbBung");

        $em = $this->getDoctrine()->getManager();
        $nbBung = $amount;

        if($amount <= 0){
            throw new \InvalidArgumentException("Amount of bungalow can not be less than 1");
        }

        for ($i = 0; $i < $nbBung; $i++) {
            $bungalow = new Bungalow();
            $bungalow->setNom("Bung ".$i);
            $bungalow->setNbPlaces(6);

            $em->persist($bungalow);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('cva_gestion_membre_config'));
    }

    /**
     * @Route("/add",name="bde_wei_bungalow_add")
     * @Method({"POST"})
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function createAction(){
        // Load data
        $em = $this->getDoctrine()->getManager();
        $form = $this->createForm(new BungalowType(), new Bungalow());

        // Handle the form
        $form->handleRequest($this->getRequest());

        // If valid, we act
        if($form->isSubmitted() && $form->isValid()){
            $em->persist($form->getData());
            $em->flush();
            $this->get('session')->getFlashBag()->add('notice', 'Bungalow ajouté');
            return $this->redirect($this->generateUrl('cva_gestion_membre_config'));
        } else { // Else, recreate the page
            return $this->forward("BdEWeiBundle:Bungalow:new");
        }
    }

    /**
     * @Route("/add",name="bde_wei_bungalow_add")
     * @Method({"GET"})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction(Request $request)
    {
        $bungalow = new Bungalow();
        $form = $this->createForm(new BungalowType(), $bungalow);
        $em = $this->getDoctrine()->getManager();

        $form->handleRequest($request);

        if($form->isValid()){
            $em->persist($form->getData());
            $em->flush();
            $this->addFlash('success',"Nouveau bungalow enregistré");
            return $this->redirectToRoute("bde_wei_bungalow");
        }

        return $this->render('BdEWeiBundle:Bungalow:add.html.twig', array('form' => $form->createView(),));
    }

    /**
     * @Route("/{id}", requirements={"id" = "\d+"},name="bde_wei_bungalow_edit")
     * @Method({"GET","POST"})
     * @param Request $request
     * @param $id mixed Params for request which describe id of bus
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $bungalow = $em->getRepository("BdEWeiBundle:Bungalow")->find($id);

        if(!$bungalow){
            throw $this->createNotFoundException("Bungalow with id [".htmlentities($id)."] is not found");
        }

        $form = $this->createForm(new BungalowType(), $bungalow);

        $form->handleRequest($request);
        if ($form->isValid())
        {
            $em->persist($form->getData());
            $em->flush();
            $this->get('session')->getFlashBag()->add('success', 'Bungalow modifié');
            return $this->redirect($this->generateUrl('bde_wei_bungalow'));
        }

        return $this->render('BdEWeiBundle:Bungalow:edit.html.twig', array('form' => $form->createView()));
    }

    /**
     * @Route("/{id}/delete", requirements={"id" = "\d+"},name="bde_wei_bungalow_delete")
     * @Method({"GET","DELETE"})
     * @param mixed $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction($id) {

        $em = $this->getDoctrine()->getManager();
        $bungalow = $em->getRepository("BdEWeiBundle:Bungalow")->find($id);

        if($bungalow->canBeDeletedSafely()){
            $this->get('session')->getFlashBag()->add('warning', 'Ce bungalow n\'est pas vide !');
            return $this->redirect($this->generateUrl('cva_gestion_membre_config'));
        }

        $em->remove($bungalow);
        $em->flush();

        $this->get('session')->getFlashBag()->add('notice', 'Bung supprimé');
        return $this->redirect($this->generateUrl('cva_gestion_membre_config'));
    }

}
