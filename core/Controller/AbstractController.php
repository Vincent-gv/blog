<?php


namespace Core\Controller;

use App\Entity\Post;
use App\Entity\User;
use Core\Database\Repository\AbstractRepository;
use Core\Database\Repository\RepositoryFactory;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

abstract class AbstractController
{
    public function getRepository(string $entity): AbstractRepository
    {
        return RepositoryFactory::createRepository($entity);
    }

    public function render(string $name, array $context = [])
    {
        $loader = new FilesystemLoader('../templates');
        $twig = new Environment($loader, [
            'strict_variables' => true
        ]);
        $this->extendTwig($twig);

        echo $twig->render($name, $context);

    }

    public function redirect(string $url)
    {
        header('Location: ' . $url);
        die();
    }

    public function extendTwig(Environment $twig)
    {

        $latest = new TwigFunction('latestPosts', function () {
            $postRepository = $this->getRepository(Post::class);
            return $postRepository->latestPosts();
        });
        $twig->addFunction($latest);

        $user = new TwigFunction('user', function () {
            if (isset($_SESSION['userConnected'])) {
                return $_SESSION['userConnected'];
            };
        });
        $twig->addFunction($user);

        $userConnected = new TwigFunction('userConnected', function () {
            $userRepository = $this->getRepository(User::class);
            return $userRepository->getUserConnected() !== null;
        });
        $twig->addFunction($userConnected);

        $getUserConnected = new TwigFunction('getUserConnected', function () {
            return $_SESSION['userConnected'] ?? null;
        });
        $twig->addFunction($getUserConnected);

    }

}