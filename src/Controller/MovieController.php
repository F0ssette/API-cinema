<?php

namespace App\Controller;

use App\Entity\Movie;
use App\Repository\ActorRepository;
use App\Repository\GenderRepository;
use App\Repository\MovieRepository;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * @Route("/movie")
 */
class MovieController extends AbstractController
{
    /**
     * @Route("/",methods={"GET"})
     */
    public function api(MovieRepository $movieRepository): Response
    {
        $encoders = [new JsonEncoder()]; // If no need for XmlEncoder
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);

        $movies = $movieRepository->findAll();
        $movies = $serializer->serialize($movies, 'json', [
            'circular_reference_handler' => function ($object) {
                return $object->getId();
            }
        ]);

        return new Response($movies, 200, ['Content-Type' => 'application/json']);
    }

    /**
     * @Route("/{id}",methods={"GET"})
     */
    public function apiDetail(MovieRepository $movieRepository, $id): Response
    {
        $encoders = [new JsonEncoder()]; // If no need for XmlEncoder
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);

        $movies = $movieRepository->find($id);
        $movies = $serializer->serialize($movies, 'json', [
            'circular_reference_handler' => function ($object) {
                return $object->getId();
            }
        ]);

        return new Response($movies, 200, ['Content-Type' => 'application/json']);
    }

    /**
     * @Route("/new",methods={"POST"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function apiNew(Request $request, GenderRepository $genderRepository, ActorRepository $actorRepository)
    {
        $encoders = [new JsonEncoder()]; // If no need for XmlEncoder
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);

        $data = json_decode($request->getContent(), true);

        $time = new \DateTime($data['year']);
        $genre = $genderRepository->find($data['gender_id']);

        $movie = new Movie($data['title'], $data['description'], $time, $data['picture'], $data['note'], $genre);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($movie);
        $entityManager->flush();

        foreach($data['actor'] as $actor) {
            $actor = $actorRepository->find($actor);
            $movie->addActor($actor);
            $entityManager->persist($movie);
        }
        $entityManager->flush();

        return $this->json($data);
    }

    /**
     * @Route("/edit/{id}", name="movie_edit", methods={"PUT"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function edit(MovieRepository $movieRepository, Request $request, GenderRepository $genderRepository, ActorRepository $actorRepository, $id)
    {
        $data = json_decode($request->getContent(), true);
        $movie = $movieRepository->find($id);

        $entityManager = $this->getDoctrine()->getManager();
        if (!$movie) {
            throw $this->createNotFoundException(
                'No movie found for id ' . $id
            );
        }

        $time = new \DateTime($data['year']);
        $genre = $genderRepository->find($data['gender_id']);

        $movie->setTitle($data['title']);
        $movie->setDescription($data['description']);
        $movie->setYear($time);
        $movie->setPicture($data['picture']);
        $movie->setNote($data['note']);
        $movie->setGender($genre);
        $entityManager->flush();

        foreach($data['actor'] as $actor) {
            $actor = $actorRepository->find($actor);
            $movie->addActor($actor);
            $entityManager->persist($movie);
        }
        $entityManager->flush();

        return $this->json("Movie edite");
    }

    /**
     * @Route("/delete/{id}", methods={"DELETE"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function delete(MovieRepository $movieRepository, $id)
    {
        $movie = $movieRepository->find($id);

        $entityManager = $this->getDoctrine()->getManager();

        $entityManager->remove($movie);
        $entityManager->flush();
        return $this->json("Film supprime");
    }
}
