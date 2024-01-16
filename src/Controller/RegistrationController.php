<?php

namespace App\Controller;

use App\Entity\Users;
use App\Form\RegistrationFormType;
use App\Repository\UsersRepository;
use App\Security\UsersAuthenticator;
use App\Service\JWTService;
use App\Service\SendMailService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Messenger\SendEmailMessage;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        UserAuthenticatorInterface $userAuthenticator,
        UsersAuthenticator $authenticator,
        EntityManagerInterface $entityManager,
        SendMailService $mail,
        JWTService $jwt
    ): Response {
        $user = new Users();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $entityManager->persist($user);
            $entityManager->flush();
            // do anything else you need here, like send an email
            //We will gerate the JWT user
            // We will create the header
            $header = [
                'type' => 'JWT',
                'alg' => 'HS256'
            ];

            // We will create the payload
            $payload = [
                'user_id' => $user->getId()
            ];

            // To create the token
            $token = $jwt->generate($header, $payload, $this->getParameter('app.jwtsecret'));

            $mail->send(
                'no-replay@test.net',
                $user->getEmail(),
                'Activation in E-Commerce website',
                'register',
                compact('user', 'token')

            );
            return $userAuthenticator->authenticateUser(
                $user,
                $authenticator,
                $request
            );
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/verify/{token}', name: 'verify_user')]
    public function verifyUser($token, JWTService $jwt, UsersRepository $usersRepository, EntityManagerInterface $em): Response
    {
        if ($jwt->isValid($token) && !$jwt->isExpired($token) && $jwt->check($token, $this->getParameter('app.jwtsecret'))) {
            // We will retreive the payload
            $payload = $jwt->getPayload($token);

            $user = $usersRepository->find($payload['user_id']);

            if ($user && !$user->getIsVerified()) {
                $user->setIsVerified(true);

                $em->flush($user);

                $this->addFlash('success', 'User is activated');

                return $this->redirectToRoute('profile_index');
            }
        }
        // if there is any problem then it show the message
        $this->addFlash("danger", "The token is invalide or it's expired");

        return $this->redirectToRoute('app_login');
    }

    // for resending the verification
    #[Route('/resendverif', name: 'resend_verif')]
    public function resendVerif(JWTService $jwt, SendMailService $mail, UsersRepository $usersRepository): Response
    {
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('danger', 'You should be connected to access this page');
            return $this->redirectToRoute('app_login');
        }

        if ($user->getIsVerified()) {
            $this->addFlash('warning', 'This user is already activated');
            return $this->redirectToRoute('profile_index');
        }

        $header = [
            'type' => 'JWT',
            'alg' => 'HS256'
        ];

        // We will create the payload
        $payload = [
            'user_id' => $user->getId()
        ];

        // To create the token
        $token = $jwt->generate($header, $payload, $this->getParameter('app.jwtsecret'));

        $mail->send(
            'no-replay@test.net',
            $user->getEmail(),
            'Activation in E-Commerce website',
            'register',
            compact('user', 'token')

        );

        $this->addFlash('success', 'The verification email is send');
        return $this->redirectToRoute('profile_index');
    }
}