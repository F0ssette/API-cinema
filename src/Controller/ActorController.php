<?php

namespace App\Controller;

use App\Entity\Actor;
use App\Repository\ActorRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

/**
 * @Route("/actor")
 */
class ActorController extends AbstractController
{
    /**
     * @Route("/", methods={"GET"})
     */
    public function api(ActorRepository $actorRepository): Response
    {
        $encoders = [new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);

        $actors = $actorRepository->findAll();
        $actors = $serializer->serialize($actors, 'json', [
            'circular_reference_handler' => function ($object) {
                return $object->getId();
            }
        ]);

        return new Response($actors, 200, ['Content-Type' => 'application/json']);
    }

    
    /**
     * @Route("/new", methods={"POST"})
     */
    public function apiNew(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        $time = new \DateTime($data['birthday']);
        
        if ($data['gender'] !== true) {
            $sexe = 'M';
        } else { $sexe = 'F'; }
        
        $actor = new Actor($data['name'], $data['firstname'], $time, $sexe, $data['nationality']);
        
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($actor);
        $entityManager->flush();
        
        return $this->json($data);
    }
    
    /**
     * @Route("/{id}",methods={"GET"})
     */
    public function apiDetail(ActorRepository $actorRepository, $id)
    {
        $encoders = [new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);

        $actors = $actorRepository->find($id);
        $actors = $serializer->serialize($actors, 'json', [
            'circular_reference_handler' => function ($object) {
                return $object->getId();
            }
        ]);
        return new Response($actors, 200, ['Content-Type' => 'application/json']);
    }
    
    /**
     * @Route("/delete/{id}", methods={"DELETE"})
     */
    public function delete(ActorRepository $actorRepository, $id)
    {
        $actor = $actorRepository->find($id);

        $entityManager = $this->getDoctrine()->getManager();

        $entityManager->remove($actor);
        $entityManager->flush();
        return $this->json("Actor supprime");
    }

    /**
     * @Route("/edit/{id}", name="actor", methods={"PUT"})
     */
    public function edit(ActorRepository $actorRepository, Request $request, $id)
    {
        $data = json_decode($request->getContent(), true);
        $actor = $actorRepository->find($id);
        $entityManager = $this->getDoctrine()->getManager();
        if (!$actor) {
            throw $this->createNotFoundException(
                'No actor found for id ' . $id
            );
        }
        $time = new \DateTime($data['birthday']);

        $actor->setName($data['name']);
        $actor->setFirstName($data['firstname']);
        $actor->setBirthday($time);
        $actor->setGender($data['gender']);
        $actor->setNationality($data['nationality']);

        $entityManager->flush();
        return $this->json("Actor edite");
    }
}
