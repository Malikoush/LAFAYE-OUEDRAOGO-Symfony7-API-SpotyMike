<?php

namespace App\Controller;

use App\Entity\Album;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Artist;
use Symfony\Component\HttpFoundation\Request;
use App\Error\ErrorTypes;
use App\Error\ErrorManager;
use Exception;
use UrlGeneratorInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class AlbumController extends AbstractController
{
    private $repository;
    private $entityManager;
    private $errorManager;

    public function __construct(EntityManagerInterface $entityManager, ErrorManager $errorManager)
    {
        $this->entityManager = $entityManager;
        $this->errorManager = $errorManager;

        $this->repository = $entityManager->getRepository(Album::class);
    }

    #[Route('/album/{id}', name: 'app_album_delete', methods: ['DELETE'])]
    public function delete_album_by_id(int $id, TokenInterface $token, JWTTokenManagerInterface $JWTManager): JsonResponse
    {
        try {
            $decodedtoken = $JWTManager->decode($token);
            $this->errorManager->TokenNotReset($decodedtoken);

            $album = $this->repository->find($id);

            $this->errorManager->checkNotFoundAlbumId($album);

            $this->entityManager->remove($album);
            $this->entityManager->flush();

            return new JsonResponse([
                'error' => false,
                'message' => "Votre album a été supprimé avec succès."
            ], 200);

            // Gestion des erreurs inattendues
            throw new Exception(ErrorTypes::UNEXPECTED_ERROR);
        } catch (Exception $exception) {
            return $this->errorManager->generateError($exception->getMessage(), $exception->getCode());
        }
    }

    #[Route('/album', name: 'post_album', methods: 'POST')]
    public function post_album(Request $request, TokenInterface $token, JWTTokenManagerInterface $JWTManager): JsonResponse
    {
        try {
            $decodedtoken = $JWTManager->decode($token);
            $this->errorManager->TokenNotReset($decodedtoken);

            parse_str($request->getContent(), $data);

            $this->errorManager->checkRequiredAttributes($data, ['nom', 'categ', 'cover', 'year']);

            $date = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
            $uniqueId = uniqid();

            $album = new Album();
            $artist = $this->entityManager->getRepository(Artist::class)->find(1);
            $album->setArtistUserIdUser($artist);
            $album->setNom($data['nom']);
            $album->setCateg($data['categ']);
            //$album->setCover($data['cover']);
            $album->setYear($data['year']);
            $album->setIdAlbum($uniqueId);
            $album->setCreateAt($date);
            $album->setUpdateAt($date);

            $this->entityManager->persist($album);
            $this->entityManager->flush();

            return new JsonResponse([
                'error' => false,
                'message' => "Album créé avec succès.",
                'id' => $album->getId()
            ], 201);

            // Gestion des erreurs inattendues
            throw new Exception(ErrorTypes::UNEXPECTED_ERROR);
        } catch (Exception $exception) {
            return $this->errorManager->generateError($exception->getMessage(), $exception->getCode());
        }
    }

    #[Route('/album/{id}/song', name: 'post_album_id_song', methods: 'POST')]
    public function post_album_id_song(int $id, Request $request, TokenInterface $token, JWTTokenManagerInterface $JWTManager): JsonResponse
    {
        try {
            $decodedtoken = $JWTManager->decode($token);
            $this->errorManager->TokenNotReset($decodedtoken);

            $album = $this->repository->find($id);

            $this->errorManager->checkNotFoundAlbumId($album);

            parse_str($request->getContent(), $data);

            if (isset($data['nom'])) {
                $album->setNom($data['nom']);
            }
            if (isset($data['categ'])) {
                $album->setCateg($data['categ']);
            }
            if (isset($data['cover'])) {
                $album->setCover($data['cover']);
            }
            if (isset($data['year'])) {
                $album->setYear($data['year']);
            }
            if (isset($data['idalbum'])) {
                $album->setIdAlbum($data['idalbum']);
            }

            $this->entityManager->persist($album);
            $this->entityManager->flush();

            return new JsonResponse([
                'error' => false,
                'message' => "Album mis à jour avec succès.",
                'idSong' => $album->getSongIdSong()
            ], 200);

            // Gestion des erreurs inattendues
            throw new Exception(ErrorTypes::UNEXPECTED_ERROR);
        } catch (Exception $exception) {
            return $this->errorManager->generateError($exception->getMessage(), $exception->getCode());
        }
    }

    #[Route('/album/{id}', name: 'app_album_put', methods: ['PUT'])]
    public function putAlbum(Request $request, int $id, TokenInterface $token, JWTTokenManagerInterface $JWTManager): JsonResponse
    {
        try {
            $decodedtoken = $JWTManager->decode($token);
            $this->errorManager->TokenNotReset($decodedtoken);

            $album = $this->repository->find($id);

            $this->errorManager->checkNotFoundAlbumId($album);

            parse_str($request->getContent(), $data);

            if (isset($data['nom'])) {
                $album->setNom($data['nom']);
            }
            if (isset($data['categ'])) {
                $album->setCateg($data['categ']);
            }
            if (isset($data['cover'])) {
                $album->setCover($data['cover']);
            }
            if (isset($data['year'])) {
                $album->setYear($data['year']);
            }
            if (isset($data['idalbum'])) {
                $album->setIdAlbum($data['idalbum']);
            }

            $this->entityManager->persist($album);
            $this->entityManager->flush();

            return new JsonResponse([
                'error' => false,
                'message' => "Album mis à jour avec succès."
            ], 200);

            // Gestion des erreurs inattendues
            throw new Exception(ErrorTypes::UNEXPECTED_ERROR);
        } catch (Exception $exception) {
            return $this->errorManager->generateError($exception->getMessage(), $exception->getCode());
        }
    }

    #[Route('/album/search', name: 'app_album_get_search', methods: ['GET'])]
    public function get_album_search(int $id, TokenInterface $token, JWTTokenManagerInterface $JWTManager): JsonResponse
    {
        try {
            $decodedtoken = $JWTManager->decode($token);
            $this->errorManager->TokenNotReset($decodedtoken);

            if (empty($id)) {
                return $this->errorManager->generateError(ErrorTypes::MISSING_ALBUM_ID);
            }

            $album = $this->repository->find($id);
            //dd($this->repository->findLabelsByAlbum($id));
            $labels = $this->repository->findLabelsByAlbum($id);
            $this->errorManager->checkNotFoundAlbumId($album);

            foreach ($labels as $label) {
                $labelId = $label->getLabelId();
                $labelnom = $labelId->getNom();
            }

            return new JsonResponse([
                "error" => false,
                "album" => $album->serializer(false, $labelnom)
            ], 200);

            // Gestion des erreurs inattendues
            throw new Exception(ErrorTypes::UNEXPECTED_ERROR);
        } catch (Exception $exception) {
            return $this->errorManager->generateError($exception->getMessage(), $exception->getCode());
        }
    }


    #[Route('/album/{id}', name: 'app_album_get_by_id', methods: ['GET'])]
    public function get_album_by_id(int $id, TokenInterface $token, JWTTokenManagerInterface $JWTManager): JsonResponse
    {
        try {
            $decodedtoken = $JWTManager->decode($token);
            $this->errorManager->TokenNotReset($decodedtoken);

            if (empty($id)) {
                return $this->errorManager->generateError(ErrorTypes::MISSING_ALBUM_ID);
            }

            $album = $this->repository->find($id);

            $this->errorManager->checkNotFoundAlbumId($album);

            return new JsonResponse([
                "error" => false,
                "album" => $album->serializer()
            ], 200);

            // Gestion des erreurs inattendues
            throw new Exception(ErrorTypes::UNEXPECTED_ERROR);
        } catch (Exception $exception) {
            return $this->errorManager->generateError($exception->getMessage(), $exception->getCode());
        }
    }

    #[Route('/albums', name: 'app_albums_get', methods: ['GET'])]
    public function get_all_albums(Request $request, TokenInterface $token, JWTTokenManagerInterface $JWTManager): JsonResponse
    {
        try {
            $decodedtoken = $JWTManager->decode($token);
            $this->errorManager->TokenNotReset($decodedtoken);

            parse_str($request->getContent(), $data);

            $albumsPerPage = 5;
            $numPage = $data["page"];

            // Récupération page demandée
            $page = $request->query->getInt('page', $numPage);

            $offset = ($page - 1) * $albumsPerPage;

            $albums = $this->repository->findBy([], null, $albumsPerPage, $offset);

            $this->errorManager->checkNotFoundAlbum($albums);

            $album_serialized = [];
            foreach ($albums as $album) {
                array_push($album_serialized, $album->serializer());
            }

            $totalAlbums = count($this->repository->findAll());

            $totalPages = ceil($totalAlbums / $albumsPerPage);

            // Vérif si page suivante existante
            $nextPage = null;
            if ($nextPage < $totalPages) {
                $nextPage = $page + 1;

                $nextPageOffset = ($nextPage - 1) * $albumsPerPage;

                // Récupération albums page suivante
                $nextPageAlbums = $this->repository->findBy([], null, $albumsPerPage, $nextPageOffset);

                $nextPageAlbumsSerialized = [];
                foreach ($nextPageAlbums as $album) {
                    array_push($nextPageAlbumsSerialized, $album->serializer());
                }
            }

            if (!empty($album_serialized)) {
                $currentSerializedContent = $album_serialized;
                $currentPage = $page;
            } else {
                // Sinon, afficher les valeurs de $nextPageAlbumsSerialized
                $currentSerializedContent = $nextPageAlbumsSerialized;
                $currentPage = $nextPage;
                $id = $album->getId();
                $labelnom = null;
                $labels = $this->repository->findLabelsByAlbum($id);

                if (!empty($labels)) {
                    foreach ($labels as $label) {
                        $labelId = $label->getLabelId();
                        $labelnom = $labelId->getNom();
                    }
                }
                $serializedAlbums[] = $album->serializer(false, $labelnom);
            }

            $response = [
                "error" => false,
                "albums" => $currentSerializedContent,
                "pagination" => [
                    "currentPage" => $currentPage,
                    "totalPages" => $totalPages,
                    "totalAlbums" => $totalAlbums
                ]
            ];

            if ($page = $nextPage) {
                $album_serialized = null;
            }

            return $this->json($response, 200);

            // Gestion des erreurs inattendues
            throw new Exception(ErrorTypes::UNEXPECTED_ERROR);
        } catch (Exception $exception) {
            return $this->errorManager->generateError($exception->getMessage(), $exception->getCode());
        }
    }
}
