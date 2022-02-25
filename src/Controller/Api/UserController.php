<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use \Symfony\Component\Validator\ConstraintViolationListInterface;

class UserController extends AbstractController
{
    /**
     * @Route("/api/register", name="api_register", methods="POST")
     */
    public function register(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordEncoder, Request $request, ValidatorInterface $validator, SerializerInterface $serializer): Response
    {
        $jsonContent = $request->getContent();
        
        $user = $serializer->deserialize($jsonContent, User::class, 'json');

        $errors = $validator->validate($user);
        
        // If there is at least one error, we return a 400
        if (count($errors) > 0) {
            
            $errorsList = [];
            foreach ($errors as $erreur) {
                $input = $erreur->getPropertyPath();
                $errorsList[$input] = $erreur->getMessage();
            }

            return $this->json(
                [
                    'error' => $errorsList
                ],
                400
            );
        }
       
        $password = $user->getPassword();
        // This is where we encode the User password (found in $ user)
        $encodedPassword = $passwordEncoder->hashPassword($user, $password);
        // We reassign the password encoded in the User
        $user->setPassword($encodedPassword);
        $user->setRoles(['ROLE_USER']);
        $user->setCreatedAt(new \DateTimeImmutable());

        // We save the user
        $entityManager->persist($user);
        $entityManager->flush();
          
        return $this->json([
                'user' => $user
            ], Response::HTTP_CREATED);
    }
    
    /**
     * @Route("/api/users", name="api_users", methods="GET")
     */
    /*
    public function user(UserRepository $userRepository): Response
    {
        $user = $this->getUser();
        $users = $userRepository->findByUserField($user);
       
        return $this->json($users, 200, [], ['groups' => 'user_read']);
    }
    */
}