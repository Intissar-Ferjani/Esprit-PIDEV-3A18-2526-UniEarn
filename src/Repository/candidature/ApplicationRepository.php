<?php

namespace App\Repository\candidature;

use App\Entity\candidature\Application;
use App\Enum\ApplicationStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;

/**
 * @extends ServiceEntityRepository<Application>
 *
 * @method Application|null find($id, $lockMode = null, $lockVersion = null)
 * @method Application|null findOneBy(array $criteria, array $orderBy = null)
 * @method Application[]    findAll()
 * @method Application[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ApplicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Application::class);
    }

    public function save(Application $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Application $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return Application[] Returns an array of Application objects for a specific project
     */
    public function findByProjectId(int $projectId): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.projectId = :val')
            ->setParameter('val', $projectId)
            ->orderBy('a.appliedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Finds applications matching search and sort criteria.
     * Complies with MVC standard: handles filtering/sorting in the persistence layer.
     */
    public function findBySearchAndSort(?\App\Entity\users\freelancer\Freelancer $freelancer, string $search, string $sortBy): array
    {
        $qb = $this->createQueryBuilder('a');

        if ($freelancer) {
            $qb->andWhere('a.freelancer = :freelancer')
               ->setParameter('freelancer', $freelancer);
        }

        if (!empty($search)) {
            // Search in cover letter or status
            $qb->andWhere('a.coverLetter LIKE :search OR a.status LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        switch ($sortBy) {
            case 'budget':
                $qb->orderBy('a.proposedBudget', 'DESC');
                break;
            case 'duration':
                $qb->orderBy('a.estimatedDuration', 'ASC');
                break;
            case 'date':
            default:
                $qb->orderBy('a.appliedAt', 'DESC');
                break;
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Returns a map of projectId => ['userID' => int, 'name' => string, 'company' => string]

     * by reading the project table directly via DBAL.
     *
     * @param int[] $projectIds
     */
    public function getClientInfoByProjectIds(array $projectIds): array
    {
        if (empty($projectIds)) return [];

        try {
            $conn = $this->getEntityManager()->getConnection();
            $sql = '
                SELECT p.idProject, u.idUser, u.name, c.company
                FROM project p
                JOIN client c   ON c.idClient = p.ClientID
                JOIN user   u   ON u.idUser   = c.userID
                WHERE p.idProject IN (:ids)
            ';
            $rows = $conn->executeQuery($sql, ['ids' => $projectIds], ['ids' => Connection::PARAM_INT_ARRAY])
                         ->fetchAllAssociative();

            $map = [];
            foreach ($rows as $row) {
                $map[(int)$row['idProject']] = [
                    'userID'  => (int)$row['idUser'],
                    'name'    => $row['name'],
                    'company' => $row['company'],
                ];
            }
            return $map;
        } catch (\Throwable $e) {
            // project table may not exist in this DB — return empty map
            return [];
        }
    }

    /**
     * @return int[]
     */
    public function findAcceptedProjectIdsForFreelancer(int $freelancerId): array
    {
        $rows = $this->createQueryBuilder('a')
            ->select('DISTINCT a.projectId')
            ->andWhere('IDENTITY(a.freelancer) = :freelancerId')
            ->andWhere('a.status = :status')
            ->setParameter('freelancerId', $freelancerId)
            ->setParameter('status', ApplicationStatus::ACCEPTED)
            ->getQuery()
            ->getScalarResult();

        return array_map(static fn (array $row): int => (int) $row['projectId'], $rows);
    }

    /**
     * @return int[]
     */
    public function findAppliedProjectIdsForFreelancer(int $freelancerId): array
    {
        $rows = $this->createQueryBuilder('a')
            ->select('DISTINCT a.projectId')
            ->andWhere('IDENTITY(a.freelancer) = :freelancerId')
            ->setParameter('freelancerId', $freelancerId)
            ->getQuery()
            ->getScalarResult();

        return array_map(static fn (array $row): int => (int) $row['projectId'], $rows);
    }
}
