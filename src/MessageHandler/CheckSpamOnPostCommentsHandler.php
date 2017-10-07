<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\MessageHandler;

use App\Entity\Comment;
use App\Entity\Post;
use App\Message\CheckSpamOnPostComments;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class CheckSpamOnPostCommentsHandler
{
    private $akismetApiKey;
    private $entityManager;
    private $httpClient;

    public function __construct(EntityManagerInterface $entityManager, string $akismetApiKey)
    {
        $this->entityManager = $entityManager;
        $this->akismetApiKey = $akismetApiKey;
        $this->httpClient = new Client();
    }

    public function __invoke(CheckSpamOnPostComments $message)
    {
        $comments = $this->entityManager->getRepository(Comment::class)->findBy([
            'post' => $message->getPostId(),
        ]);

        foreach ($comments as $comment) {
            if ($this->commentIsSpam($comment)) {
                $this->entityManager->remove($comment);
            }
        }
    }

    private function commentIsSpam(Comment $comment): bool
    {
        try {
            $response = $this->httpClient->request('post', sprintf('https://%s.rest.akismet.com/1.1/comment-check', $this->akismetApiKey), [
                'form_params' => [
                    'blog' => $_SERVER['HTTP_ORIGIN'] ?? 'http://localhost',
                    'user_ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                    'comment_content' => $comment->getContent(),
                    'comment_author' => $comment->getAuthor()->getFullName(),
                ],
            ]);
        } catch (RequestException $e) {
            throw new \RuntimeException(sprintf('Was unable to check spam status of comment #%d', $comment->getId()), $e->getCode(), $e);
        }

        return 'true' === $response->getBody()->getContents();
    }
}
