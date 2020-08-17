<?php
declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use GuzzleHttp\Client;

class HomeController extends AbstractController
{
    /**
     * Constans variable for cache expiration in seconds
     */
    public const CACHE_EXPIRATION = 60;

    /**
     * Home method for route /
     *
     * @return object
     */
    public function index(): object
    {   
        $cache      = new FilesystemAdapter();
        $cacheData  = $cache->getItem('guzzle.data');

        if (!$cacheData->isHit()) {
            $client         = new Client();

            try {
                $response = $client->get('https://jsonplaceholder.typicode.com/comments');
                
            } catch (\Exception $e) {
                throw new \Exception ($e->getMessage());
            }
            
            $responseCode   = $response->getStatusCode();
            $comments       = [];

            if (200 !== $responseCode) {
                throw $this->createNotFoundException('Something goes wrong with data!');
            }

            $body = $response->getBody();
            if (true === empty($body)) {
                $this->addFlash(
                    'error',
                    'Empty body!'
                );
            } else {
                $comments = json_decode($body->getContents(), true);
            }

            $cacheData->set($comments);
            $cacheData->expiresAfter(self::CACHE_EXPIRATION); 

            $cache->save($cacheData);
        }

        $comments = $cache->getItem('guzzle.data')
                        ->get();
 
        return $this->render('home/index.html.twig', [
            'comments'          => $comments
        ]);
    }
}
