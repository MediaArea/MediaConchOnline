<?php

namespace AppBundle\Lib\Quotas;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Doctrine\ORM\EntityManager;

use AppBundle\Entity\User;
use AppBundle\Entity\UserQuotas;
use AppBundle\Lib\XslPolicy\XslPolicyGetPoliciesCount;
use AppBundle\Lib\MediaConch\MediaConchServerException;

class Quotas
{
    protected $user;
    protected $em;
    protected $policiesCount;
    protected $defaultQuotas;
    protected $date;
    protected $datePeriod;

    public function __construct(TokenStorageInterface $tokenStorage, EntityManager $em, XslPolicyGetPoliciesCount $policiesCount)
    {
        $this->user = $this->getUser($tokenStorage);
        $this->em = $em;
        $this->policiesCount = $policiesCount;
        $this->defaultQuotas = $this->getDefaultQuotas();
        $this->date = new \DateTime();
        // Clone DateTime object as add/sub method modify the object itself
        $this->datePeriod = clone $this->date;
        $this->datePeriod->sub(new \DateInterval('PT' . $this->defaultQuotas['period'] . 'S'));
    }

    public function setQuotasForNewUser()
    {
        // Check if quotas have already been set
        $userQuotas = $this->em
            ->getRepository('AppBundle:UserQuotas')
            ->findOneByUser($this->user);

        if ($userQuotas) {
            return $this->resetQuotas();
        }

        $userQuotas = new UserQuotas();
        $userQuotas->setPolicies($this->defaultQuotas['policies'])
            ->setUploads($this->defaultQuotas['uploads'])
            ->setUploadsTimestamp($this->datePeriod)
            ->setUrls($this->defaultQuotas['urls'])
            ->setUrlsTimestamp($this->datePeriod)
            ->setPolicyChecks($this->defaultQuotas['policyChecks'])
            ->setPolicyChecksTimestamp($this->datePeriod)
            ->setUser($this->user);

        $this->em->persist($userQuotas);
        $this->em->flush();

        return $userQuotas;
    }

    public function resetQuotas()
    {
        $userQuotas = $this->em
            ->getRepository('AppBundle:UserQuotas')
            ->findOneByUser($this->user);

        if ($userQuotas) {
            $userQuotas->setPolicies($this->defaultQuotas['policies'])
                ->setUploads($this->defaultQuotas['uploads'])
                ->setUploadsTimestamp($this->datePeriod)
                ->setUrls($this->defaultQuotas['urls'])
                ->setUrlsTimestamp($this->datePeriod)
                ->setPolicyChecks($this->defaultQuotas['policyChecks'])
                ->setPolicyChecksTimestamp($this->datePeriod)
                ->setUser($this->user);
            $this->em->flush();
        }
        else {
            $userQuotas = $this->setQuotasForNewUser();
        }

        return $userQuotas;
    }

    public function getQuotasForProfile()
    {
        $userQuotas = $this->getQuotasByUser();
        $userQuotas->getUploadsTimestamp()->add(new \DateInterval('PT' . $this->defaultQuotas['period'] . 'S'));
        $userQuotas->getUrlsTimestamp()->add(new \DateInterval('PT' . $this->defaultQuotas['period'] . 'S'));
        $userQuotas->getPolicyChecksTimestamp()->add(new \DateInterval('PT' . $this->defaultQuotas['period'] . 'S'));

        $quotas = array('policiesUsed' => $this->countPolicies(),
            'policiesQuota' => $this->defaultQuotas['policies'],
            'uploadsUsed' => ($userQuotas->getUploadsTimestamp() < $this->date) ? 0 : $this->defaultQuotas['uploads'] - $userQuotas->getUploads(),
            'uploadsQuota' => $this->defaultQuotas['uploads'],
            'uploadsLimit' => ($userQuotas->getUploadsTimestamp() < $this->date) ? false : $userQuotas->getUploadsTimestamp(),
            'urlsUsed' => ($userQuotas->getUrlsTimestamp() < $this->date) ? 0 : $this->defaultQuotas['urls'] - $userQuotas->getUrls(),
            'urlsQuota' => $this->defaultQuotas['urls'],
            'urlsLimit' => ($userQuotas->getUrlsTimestamp() < $this->date) ? false : $userQuotas->getUrlsTimestamp(),
            'policyChecksUsed' => ($userQuotas->getPolicyChecksTimestamp() < $this->date) ? 0 : $this->defaultQuotas['policyChecks'] - $userQuotas->getPolicyChecks(),
            'policyChecksQuota' => $this->defaultQuotas['policyChecks'],
            'policyChecksLimit' => ($userQuotas->getPolicyChecksTimestamp() < $this->date) ? false : $userQuotas->getPolicyChecksTimestamp(),
            );

        return $quotas;
    }

    public function hasUploadsRights()
    {
        $userQuotas = $this->getQuotasByUser();
        if ($userQuotas->getUploadsTimestamp() < $this->datePeriod) {
            return true;
        }
        else {
            return 0 < $userQuotas->getUploads();
        }
    }

    public function hitUploads($uploads = 1)
    {
        $userQuotas = $this->getQuotasByUser();
        if ($userQuotas->getUploadsTimestamp() < $this->datePeriod) {
            $userQuotas->setUploads($this->defaultQuotas['uploads'] - $uploads)->setUploadsTimestamp($this->date);
        }
        else {
            $userQuotas->decreaseUploads($uploads);
        }

        $this->em->persist($userQuotas);
        $this->em->flush();
    }

    public function hasUrlsRights()
    {
        $userQuotas = $this->getQuotasByUser();
        if ($userQuotas->getUrlsTimestamp() < $this->datePeriod) {
            return true;
        }
        else {
            return 0 < $userQuotas->getUrls();
        }
    }

    public function hitUrls($urls = 1)
    {
        $userQuotas = $this->getQuotasByUser();
        if ($userQuotas->getUrlsTimestamp() < $this->datePeriod) {
            $userQuotas->setUrls($this->defaultQuotas['urls'] - $urls)->setUrlsTimestamp($this->date);
        }
        else {
            $userQuotas->decreaseUrls($urls);
        }

        $this->em->persist($userQuotas);
        $this->em->flush();
    }

    public function hasPolicyChecksRights()
    {
        $userQuotas = $this->getQuotasByUser();
        if ($userQuotas->getPolicyChecksTimestamp() < $this->datePeriod) {
            return true;
        }
        else {
            return 0 < $userQuotas->getPolicyChecks();
        }
    }

    public function hitPolicyChecks($policyChecks = 1)
    {
        $userQuotas = $this->getQuotasByUser();
        if ($userQuotas->getPolicyChecksTimestamp() < $this->datePeriod) {
            $userQuotas->setPolicyChecks($this->defaultQuotas['policyChecks'] - $policyChecks)->setPolicyChecksTimestamp($this->date);
        }
        else {
            $userQuotas->decreasePolicyChecks($policyChecks);
        }

        $this->em->persist($userQuotas);
        $this->em->flush();
    }

    public function hasPolicyCreationRights()
    {
        return $this->countPolicies() < $this->defaultQuotas['policies'];
    }

    private function countPolicies()
    {
        try {
            $this->policiesCount->getPoliciesCount();
            $xslPolicy = $this->policiesCount->getResponse()->getCount();
        }
        catch (MediaConchServerException $e) {
            $xslPolicy = 0;
        }

        $display = $this->em
            ->getRepository('AppBundle:DisplayFile')
            ->createQueryBuilder('p')
                ->select('COUNT(p)')
                ->where('p.user = :user')
                ->setParameter('user', $this->user)
            ->getQuery()
            ->getSingleScalarResult();

        return $xslPolicy + $display;
    }

    private function getUser($tokenStorage)
    {
        $token = $tokenStorage->getToken();

        if ($token !== null && $token->getUser() instanceof User) {
            return $token->getUser();
        }
        else {
            throw new \Exception('Invalid User');
        }
    }

    private function getQuotasByUser()
    {
        $userQuotas = $this->em
            ->getRepository('AppBundle:UserQuotas')
            ->findOneByUser($this->user);

        if (!$userQuotas) {
            $userQuotas = $this->setQuotasForNewUser();
        }

        return $userQuotas;
    }

    private function getDefaultQuotas()
    {
        if ($this->user->hasRole('ROLE_ADMIN')) {
            $defaultQuotas = array('period' => 3600,
                'policies' => 200,
                'uploads' => 100,
                'urls' => 100,
                'policyChecks' => 1000,
            );
        }
        else if ($this->user->hasRole('ROLE_BASIC')) {
            $defaultQuotas = array('period' => 3600,
                'policies' => 20,
                'uploads' => 10,
                'urls' => 10,
                'policyChecks' => 200,
            );
        }
        else {
            $defaultQuotas = array('period' => 3600,
                'policies' => 10,
                'uploads' => 3,
                'urls' => 3,
                'policyChecks' => 100,
            );
        }

        $defaultUserQuotas = $this->em
        ->getRepository('AppBundle:UserQuotasDefault')
        ->findOneByUser($this->user);
        if ($defaultUserQuotas) {
            $defaultQuotas = array('period' => 3600,
                'policies' => $defaultUserQuotas->getPolicies(),
                'uploads' => $defaultUserQuotas->getUploads(),
                'urls' => $defaultUserQuotas->getUrls(),
                'policyChecks' => $defaultUserQuotas->getPolicyChecks(),
            );
        }

        return $defaultQuotas;
    }
}
