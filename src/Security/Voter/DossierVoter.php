<?php

namespace App\Security\Voter;

use App\Entity\Dossier;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Workflow\Registry;

class DossierVoter extends Voter
{
    public const VIEW = 'DOSSIER_VIEW';
    public const EDIT = 'DOSSIER_EDIT';
    public const TRANSITION = 'DOSSIER_TRANSITION';

    public function __construct(
        private Registry $workflowRegistry
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof Dossier
            && in_array($attribute, [
                self::VIEW,
                self::EDIT,
                self::TRANSITION
            ], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if (!$subject instanceof Dossier) {
            return false;
        }

        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        return match ($attribute) {
            self::VIEW => $this->canView($subject, $user),
            self::EDIT => $this->canEdit($subject, $user),
            self::TRANSITION => $this->canTransition($subject, $user),
            default => false,
        };
    }

    // =========================================================
    // VIEW
    // =========================================================
    private function canView(Dossier $dossier, User $user): bool
    {
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        if (in_array('ROLE_MANAGER', $user->getRoles(), true)) {
            return true;
        }

        $customer = $user->getCustomer();

        return $customer !== null && $dossier->getCustomer() === $customer;
    }

    // =========================================================
    // EDIT
    // =========================================================
    private function canEdit(Dossier $dossier, User $user): bool
    {
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        if (!in_array('ROLE_MANAGER', $user->getRoles(), true)) {
            return false;
        }

        $workflow = $this->workflowRegistry->get($dossier, 'dossier');

        return $workflow->can($dossier, 'select_vehicle')
            || $workflow->can($dossier, 'request_documents');
    }

    // =========================================================
    // TRANSITION
    // =========================================================
    private function canTransition(Dossier $dossier, User $user): bool
    {
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        if (!in_array('ROLE_MANAGER', $user->getRoles(), true)) {
            return false;
        }

        $customer = $user->getCustomer();

        if ($customer === null) {
            return false;
        }

        if ($dossier->getCustomer() === $customer) {
            return true;
        }

        $workflow = $this->workflowRegistry->get($dossier, 'dossier');

        return $workflow->can($dossier, 'select_vehicle');
    }
}
