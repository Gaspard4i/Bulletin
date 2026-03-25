<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Bulletin;
use App\Entity\Grade;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // ── Admin ───────────────────────────────────────────────────────
        $admin = $this->createUser(
            $manager,
            'admin@univ-lille.fr',
            'Admin',
            'Bulletin',
            ['ROLE_ADMIN'],
            'admin123',
        );

        // ── Scolarité ──────────────────────────────────────────────────
        $scolarite = $this->createUser(
            $manager,
            'scolarite@univ-lille.fr',
            'Marie',
            'Dupont',
            ['ROLE_SCOLARITE'],
            'scolarite123',
        );

        // ── Teachers ───────────────────────────────────────────────────
        $teacher1 = $this->createUser(
            $manager,
            'jean.martin@univ-lille.fr',
            'Jean',
            'Martin',
            ['ROLE_TEACHER'],
            'teacher123',
        );

        $teacher2 = $this->createUser(
            $manager,
            'sophie.bernard@univ-lille.fr',
            'Sophie',
            'Bernard',
            ['ROLE_TEACHER'],
            'teacher123',
        );

        // ── Students ──────────────────────────────────────────────────
        $students = [];
        $studentData = [
            ['pierre.durand@etu.univ-lille.fr', 'Pierre', 'Durand'],
            ['camille.leroy@etu.univ-lille.fr', 'Camille', 'Leroy'],
            ['lucas.moreau@etu.univ-lille.fr', 'Lucas', 'Moreau'],
            ['emma.petit@etu.univ-lille.fr', 'Emma', 'Petit'],
            ['hugo.roux@etu.univ-lille.fr', 'Hugo', 'Roux'],
        ];

        foreach ($studentData as [$email, $firstName, $lastName]) {
            $students[] = $this->createUser(
                $manager,
                $email,
                $firstName,
                $lastName,
                ['ROLE_STUDENT'],
                'student123',
            );
        }

        // ── Subjects & Grades ──────────────────────────────────────────
        $subjects = [
            ['Algorithmique', 3.0],
            ['Base de données', 2.0],
            ['Programmation Web', 2.5],
            ['Réseaux', 2.0],
            ['Mathématiques', 3.0],
            ['Anglais', 1.5],
        ];

        foreach ($students as $student) {
            // Create a bulletin for S1
            $bulletin = new Bulletin();
            $bulletin->setStudent($student);
            $bulletin->setSemester('S1');
            $bulletin->setAcademicYear('2025-2026');
            $manager->persist($bulletin);

            // Add grades for each subject
            foreach ($subjects as [$subjectName, $coefficient]) {
                $grade = new Grade();
                $grade->setBulletin($bulletin);
                $grade->setSubject($subjectName);
                $grade->setGrade(round(mt_rand(40, 200) / 10, 1)); // Random 4.0 - 20.0
                $grade->setCoefficient($coefficient);
                $manager->persist($grade);
            }
        }

        $manager->flush();
    }

    private function createUser(
        ObjectManager $manager,
        string $email,
        string $firstName,
        string $lastName,
        array $roles,
        string $plainPassword,
    ): User {
        $user = new User();
        $user->setEmail($email);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setRoles($roles);
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));

        $manager->persist($user);

        return $user;
    }
}
