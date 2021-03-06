<?php

namespace App\Controller;

use App\Entity\User;
use Core\Controller\AbstractController;
use Core\Util\CSRF;
use Core\Util\FlashBag;

class UserController extends AbstractController
{
    public function __invoke()
    {
        $this->redirectAnonymousUser();
        $userRepository = $this->getRepository(User::class);
        $formUser = new User();
        $formUser->setUsername($_POST['username'] ?? null);
        $formUser->setEmail($_POST['email'] ?? null);
        $formUser->setPassword($_POST['password'] ?? null);
        $errors = [];
        $csrfToken = $_POST['csrfToken'] ?? '';
        if ('POST' === $_SERVER['REQUEST_METHOD']) {
            if (empty($formUser->getUsername())) {
                $errors['user'][] = 'Le nom d\'utilisateur ne peut pas être vide';
            } elseif (strlen($formUser->getUsername()) < 3) {
                $errors['user'][] = 'Le nom d\'utilisateur doit faire 3 caractères ou plus';
            }
            if (empty($formUser->getEmail())) {
                $errors['user'][] = 'Le mail ne peut pas être vide';
            } elseif (!filter_var($formUser->getEmail(), FILTER_VALIDATE_EMAIL)) {
                $errors['user'][] = 'Le mail indiqué n\'est pas valide';
            }
            if (empty($formUser->getPassword())) {
                $errors['user'][] = 'Le mot de passe ne peut pas être vide';
            } elseif (strlen($formUser->getPassword()) < 3) {
                $errors['user'][] = 'Le mot de passe doit faire 3 caractères ou plus';
            }
            if (!CSRF::checkToken($csrfToken)) {
                $errors['token'][] = 'Token invalide, veuillez renvoyer le formulaire';
            }
            if (empty($errors)) {
                usleep(500000);
                FlashBag::addFlash('Le nouvel utilsateur a bien été créé.', 'success');
                $userRepository->createUser($formUser);
                $this->redirect('/admin');
            }
        }
        $this->echoRender('Default/user.html.twig', [
            'errors' => $errors,
            'csrfToken' => CSRF::generateToken(),
            'formUser' => $formUser
        ]);
    }
}
