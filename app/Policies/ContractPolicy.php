<?php

namespace App\Policies;

use App\Enums\ContractStatus;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ContractPolicy
{
    /**
     * A cancelled contract is frozen: its data cannot be edited.
     *
     * The rule is purely about contract status — there is no ownership concept — so
     * it is evaluated for guests too (the API layer is unauthenticated).
     */
    public function update(?User $user, Contract $contract): Response
    {
        return $this->denyWhenCancelled($contract);
    }

    /**
     * A cancelled contract cannot have services added.
     */
    public function addItem(?User $user, Contract $contract): Response
    {
        return $this->denyWhenCancelled($contract);
    }

    /**
     * A cancelled contract cannot have services removed.
     */
    public function removeItem(?User $user, Contract $contract): Response
    {
        return $this->denyWhenCancelled($contract);
    }

    private function denyWhenCancelled(Contract $contract): Response
    {
        return $contract->status === ContractStatus::Cancelled
            ? Response::deny('Um contrato cancelado não pode ser editado.')
            : Response::allow();
    }
}
