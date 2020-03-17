<?php

namespace App\Controller;

use App\Entity\Gender;
use App\Repository\GenderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

/**
 * @Route("/gender")
 */
class GenderController extends AbstractController
{
    /**
     * @Route("/", methods={"GET"})
     */
    public function api(GenderRepository $genderRepository): Response
    {
        $encoders = [new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);

        $genders = $genderRepository->findAll();
        $genders = $serializer->serialize($genders, 'json', [
            'circular_reference_handler' => function ($object) {
                return $object->getId();
            }
        ]);

        return new Response($genders, 200, ['Content-Type' => 'application/json']);
    }

    /**
     * @Route("/{id}",methods={"GET"})
     */
    public function apiDetail(GenderRepository $genderRepository, $id)
    {
        $encoders = [new JsonEncoder()]; // If no need for XmlEncoder
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);

        $genders = $genderRepository->find($id);
        $genders = $serializer->serialize($genders, 'json', [
            'circular_reference_handler' => function ($object) {
                return $object->getId();
            }
        ]);
        return new Response($genders, 200, ['Content-Type' => 'application/json']);
    }

    /**
     * @Route("/new", methods={"POST"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function apiNew(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        $gender = new Gender($data['name']);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($gender);
        $entityManager->flush();

        return $this->json($data);
    }

    /**
     * @Route("/delete/{id}", methods={"DELETE"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function delete(GenderRepository $genderRepository, $id)
    {
        $gender = $genderRepository->find($id);

        $entityManager = $this->getDoctrine()->getManager();

        $entityManager->remove($gender);
        $entityManager->flush();
        return $this->json("Gender supprime");
    }

    /**
     * @Route("/edit/{id}", name="gender", methods={"PUT"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function edit(GenderRepository $genderRepository, Request $request, $id)
    {
        $data = json_decode($request->getContent(), true);
        $gender = $genderRepository->find($id);
        $entityManager = $this->getDoctrine()->getManager();
        if (!$gender) {
            throw $this->createNotFoundException(
                'No gender found for id ' . $id
            );
        }
        $gender->setName($data['name']);
        $entityManager->flush();
        return $this->json("Gender edite");
    }
}
