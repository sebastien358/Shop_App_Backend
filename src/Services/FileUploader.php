<?php

namespace App\Services;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUploader
{
    private $targetDirectory;
    private EntityManagerInterface $entityManager;

    public function __construct(string $targetDirectory, EntityManagerInterface $entityManager)
    {
        $this->targetDirectory = $targetDirectory;
        $this->entityManager = $entityManager;
    }

    public function upload(UploadedFile $file)
    {
        $fileName = md5(uniqid()) . '.' . $file->guessExtension();
        $file->move($this->targetDirectory, $fileName);
        return $fileName;
    }

    public function removeProductImage($images)
    {
        if ($images) {
            foreach ($images as $picture) {
                $fileName = $this->targetDirectory . '/' . $picture->getFileName();
                if (file_exists($fileName)) {
                    unlink($fileName);
                }
                $this->entityManager->remove($picture);
            }
        }
    }
}
