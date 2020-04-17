<?php

namespace Core\Controller;

use App\Entity\Post;
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
    }

    public function isConnected(): bool
    {
        return isset($_SESSION['userConnected']);
    }

    public function redirectAnonymousUser(): void
    {
        if (!$this->isConnected()) {
            $this->redirect('./admin');
        }
    }

    public function extendTwig(Environment $twig)
    {
        // show latest posts
        $latest = new TwigFunction('latestPosts', function () {
            $postRepository = $this->getRepository(Post::class);
            return $postRepository->latestPosts();
        });
        $twig->addFunction($latest);

        // verify if user is connected
        $user = new TwigFunction('user', function () {
            if ($this->isConnected()) {
                return $_SESSION['userConnected'];
            };
        });
        $twig->addFunction($user);

        // verify captcha
        $captcha = new TwigFunction('captcha', function () {
            $reCaptcha = new ReCaptcha('6Leh4-kUAAAAAJOHUwnl6_p9KkyG8qCtdIYKY7NR');
            if(isset($_POST["g-recaptcha-response"])) {
                $resp = $reCaptcha->verifyResponse(
                    $_SERVER["REMOTE_ADDR"],
                    $_POST["g-recaptcha-response"]
                );
                if ($resp != null && $resp->success) {echo "OK";}
                else {echo "CAPTCHA incorrect";}
            }
        });
        $twig->addFunction($captcha);
    }
}