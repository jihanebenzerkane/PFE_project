<?php

namespace App\Services;

use App\Models\Creneau;
use App\Models\Enseignant;
use App\Models\Jury;
use App\Models\Soutenance;
use Illuminate\Support\Facades\Log;

class ConstraintValidator
{
    public function validateAll(
        int $ensId,
        int $creneauId,
        int $salleId,
        Jury $jury
    ): bool {
        return $this->validateCapacity($salleId, $creneauId)
            && $this->validateNoConflict($ensId, $creneauId)
            && $this->validateQuotas($ensId)
            && $this->validateJuryComposition($jury)
            && $this->validateDailyBalance($ensId, $creneauId);
    }

    public function validateCapacity(int $salleId, int $creneauId): bool
    {
        $isOccupied = Soutenance::where('salle_id', $salleId)
            ->where('creneau_id', $creneauId)
            ->exists();

        if ($isOccupied) {
            Log::warning('Slot rejected: salle already occupied.', [
                'salle_id' => $salleId,
                'creneau_id' => $creneauId,
            ]);
        }

        return !$isOccupied;
    }

    public function validateNoConflict(int $ensId, int $creneauId): bool
    {
        $slotOrder = $this->slotOrder();
        $targetCreneau = Creneau::find($creneauId);
        $targetSlot = $this->getSlotIndex($targetCreneau, $slotOrder);

        if (!$targetCreneau || $targetSlot === null) {
            Log::warning('Slot rejected: official slot not found.', [
                'enseignant_id' => $ensId,
                'creneau_id' => $creneauId,
            ]);
            return false;
        }

        $targetDate = $targetCreneau->date->format('Y-m-d');
        $busySlots = $this->getProfessorBusySlots($ensId, $targetDate, $slotOrder);

        foreach ($busySlots as $busySlot) {
            if (abs($targetSlot - $busySlot) <= 1) {
                Log::info('Professor rejected by hard rest rule.', [
                    'enseignant_id' => $ensId,
                    'creneau_id' => $creneauId,
                    'target_slot' => $targetSlot,
                    'busy_slot' => $busySlot,
                    'date' => $targetDate,
                ]);
                return false;
            }
        }

        return true;
    }

    public function validateQuotas(int $ensId): bool
    {
        $enseignant = Enseignant::find($ensId);
        if (!$enseignant) {
            return false;
        }

        $nbJurys = Jury::whereHas('enseignants', function ($q) use ($ensId) {
            $q->where('enseignant_id', $ensId);
        })->count();

        $quota = $enseignant->quota_max ?? max(1, (int) ceil(Jury::count() / max(1, Enseignant::count())) + 2);
        $isValid = $nbJurys < $quota;

        if (!$isValid) {
            Log::info('Professor rejected by overload quota.', [
                'enseignant_id' => $ensId,
                'current_jury_count' => $nbJurys,
                'quota' => $quota,
            ]);
        }

        return $isValid;
    }

    public function validateJuryComposition(Jury $jury): bool
    {
        $membres = $jury->enseignants;
        $memberIds = $membres->pluck('id')->all();

        if (count($memberIds) !== count(array_unique($memberIds))) {
            Log::warning('Duplicate detected: duplicate professor in jury.', [
                'jury_id' => $jury->id,
                'member_ids' => $memberIds,
            ]);
            return false;
        }

        if ($membres->count() !== 3) {
            Log::warning('Jury rejected: invalid member count.', [
                'jury_id' => $jury->id,
                'member_count' => $membres->count(),
            ]);
            return false;
        }

        $soutenance = $jury->soutenance;
        if (!$soutenance) {
            return true;
        }

        if ($soutenance->encadrant_id && in_array($soutenance->encadrant_id, $memberIds, true)) {
            Log::warning('Duplicate detected: encadrant also appears in jury.', [
                'jury_id' => $jury->id,
                'soutenance_id' => $soutenance->id,
                'encadrant_id' => $soutenance->encadrant_id,
            ]);
            return false;
        }

        $slotOrder = $this->slotOrder();
        $creneau = $soutenance->creneau;
        $slotIndex = $this->getSlotIndex($creneau, $slotOrder);

        if (!$creneau || $slotIndex === null) {
            return false;
        }

        $date = $creneau->date->format('Y-m-d');
        foreach ($memberIds as $memberId) {
            foreach ($this->getProfessorBusySlots($memberId, $date, $slotOrder, $soutenance->id) as $busySlot) {
                if (abs($slotIndex - $busySlot) <= 1) {
                    Log::info('Professor rejected from jury by adjacent-slot rest rule.', [
                        'jury_id' => $jury->id,
                        'soutenance_id' => $soutenance->id,
                        'enseignant_id' => $memberId,
                        'slot' => $slotIndex,
                        'busy_slot' => $busySlot,
                        'date' => $date,
                    ]);
                    return false;
                }
            }
        }

        return true;
    }

    public function validateDailyBalance(int $ensId, int $creneauId): bool
    {
        $creneau = Creneau::find($creneauId);
        if (!$creneau) {
            return false;
        }

        $date = $creneau->date->format('Y-m-d');
        $total = count($this->getProfessorBusySlots($ensId, $date, $this->slotOrder()));
        $maxDailyLoad = 3;
        $isValid = $total < $maxDailyLoad;

        if (!$isValid) {
            Log::info('Professor rejected by daily overload.', [
                'enseignant_id' => $ensId,
                'date' => $date,
                'daily_load' => $total,
                'max_daily_load' => $maxDailyLoad,
            ]);
        }

        return $isValid;
    }

    private function getProfessorBusySlots(
        int $ensId,
        string $date,
        array $slotOrder,
        ?int $ignoreSoutenanceId = null
    ): array {
        $busySlots = [];

        $query = Soutenance::with('creneau', 'jury.enseignants')
            ->whereHas('creneau', fn($q) => $q->where('date', $date))
            ->where(function ($query) use ($ensId) {
                $query->where('encadrant_id', $ensId)
                    ->orWhereHas('jury.enseignants', fn($q) => $q->where('enseignant_id', $ensId));
            });

        if ($ignoreSoutenanceId) {
            $query->where('id', '!=', $ignoreSoutenanceId);
        }

        foreach ($query->get() as $soutenance) {
            $slotIndex = $this->getSlotIndex($soutenance->creneau, $slotOrder);
            if ($slotIndex !== null) {
                $busySlots[$slotIndex] = $slotIndex;
            }
        }

        return array_values($busySlots);
    }

    private function slotOrder(): array
    {
        return [
            '09:00' => 0,
            '10:00' => 1,
            '11:00' => 2,
            '14:00' => 3,
            '15:00' => 4,
            '16:00' => 5,
            '17:00' => 6,
        ];
    }

    private function getSlotIndex(?Creneau $creneau, array $slotOrder): ?int
    {
        if (!$creneau) {
            return null;
        }

        $slotKey = is_object($creneau->heure_debut)
            ? $creneau->heure_debut->format('H:i')
            : substr((string) $creneau->heure_debut, 0, 5);

        return $slotOrder[$slotKey] ?? null;
    }
}
