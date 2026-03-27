<?php

namespace App\Services;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use App\Entity\Command;

class CommandService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager) {
        $this->entityManager = $entityManager;
    }

    public function getCommandData(Request $request, $commands, SerializerInterface $serializer): ?array
    {
        if (is_array($commands)) {

            $dataCommands = $serializer->normalize($commands, 'json', ['groups' =>
                ['commands', 'commandItems', 'products', 'pictures'],
                'circular_reference_handler' => function ($object) {
                    return $object->getId();
                }
            ]);

            $urlFilename = $request->getSchemeAndHttpHost() . '/images/';
            foreach ($dataCommands as &$command) {
                if (!empty($command['commandItems'])) {
                    foreach ($command['commandItems'] as &$commandItem) {
                        foreach ($commandItem['product']['pictures'] as &$picture) {
                            if (!empty($picture['filename'])) {
                                $picture['filename'] = $urlFilename . $picture['filename'];
                            }
                        }
                    }
                } else {
                    $command['commandItems'] = [];
                }
            }

            return $dataCommands;
        } else {
            $dataCommand = $serializer->normalize($commands, 'json', ['groups' =>
                ['commands', 'commandItems', 'products', 'pictures'],
                'circular_reference_handler' => function ($object) {
                    return $object->getId();
                }
            ]);

            return $dataCommand;
        }
    }

    public function handleCommands(): void
    {
        $now = new \DateTimeImmutable();

        $commands = $this->entityManager
            ->getRepository(Command::class)
            ->findBy(['status' => Command::STATUS_PENDING]);

        foreach ($commands as $command) {
            // Sécurité : ignore si updatedAt null
            if (!$command->getUpdatedAt()) {
                continue;
            }

            $deadline = $command->getUpdatedAt()->modify('+30 minutes');

            if ($deadline <= $now) {
                $this->entityManager->remove($command);
            }
        }

        $this->entityManager->flush();
    }
}
