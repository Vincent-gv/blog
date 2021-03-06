<?php

namespace App\Controller;

use App\Entity\Post;
use Core\Controller\AbstractController;

class BlogController extends AbstractController
{
    public function __invoke()
    {
        $postRepository = $this->getRepository(Post::class);
        $pageIndex = intval($_GET['page'] ?? 1);
        $pagination = $postRepository->pagination($pageIndex, 3);
        $this->echoRender('Default/blog.html.twig', [
            'posts' => $postRepository,
            'pagination' => $pagination
        ]);
    }
}
