<?php

declare(strict_types=1);

function time_to_minutes(string $time): int
{
    [$hour, $minute] = array_map('intval', explode(':', substr($time, 0, 5)));
    return ($hour * 60) + $minute;
}

function minutes_to_time(int $minutes): string
{
    return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
}

function ranges_overlap(int $startA, int $endA, int $startB, int $endB): bool
{
    return $startA < $endB && $endA > $startB;
}

function barber_can_do_service(PDO $pdo, int $barberId, int $serviceId): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM barber_services WHERE barber_id = ? AND service_id = ?');
    $stmt->execute([$barberId, $serviceId]);
    return (int) $stmt->fetchColumn() > 0;
}

function slot_is_available(PDO $pdo, int $barberId, int $serviceId, string $date, string $startTime): bool
{
    $serviceStmt = $pdo->prepare('SELECT duration_minutes, active FROM services WHERE id = ?');
    $serviceStmt->execute([$serviceId]);
    $service = $serviceStmt->fetch();
    if (!$service || !(int) $service['active']) {
        return false;
    }

    $barberStmt = $pdo->prepare('SELECT * FROM barbers WHERE id = ? AND active = 1');
    $barberStmt->execute([$barberId]);
    $barber = $barberStmt->fetch();
    if (!$barber || !barber_can_do_service($pdo, $barberId, $serviceId)) {
        return false;
    }

    $dateObject = DateTimeImmutable::createFromFormat('Y-m-d', $date);
    if (!$dateObject || $date < date('Y-m-d')) {
        return false;
    }

    $dayNumber = (int) $dateObject->format('N');
    $workDays = array_filter(array_map('intval', explode(',', (string) $barber['work_days'])));
    if (!in_array($dayNumber, $workDays, true)) {
        return false;
    }

    $start = time_to_minutes($startTime);
    $end = $start + (int) $service['duration_minutes'];
    $workStart = time_to_minutes($barber['work_start']);
    $workEnd = time_to_minutes($barber['work_end']);

    if ($start < $workStart || $end > $workEnd) {
        return false;
    }

    if (!empty($barber['lunch_start']) && !empty($barber['lunch_end'])) {
        $lunchStart = time_to_minutes($barber['lunch_start']);
        $lunchEnd = time_to_minutes($barber['lunch_end']);
        if (ranges_overlap($start, $end, $lunchStart, $lunchEnd)) {
            return false;
        }
    }

    $stmt = $pdo->prepare(
        "SELECT start_time, end_time FROM appointments
         WHERE barber_id = ? AND appointment_date = ?
         AND status NOT IN ('cancelled', 'no_show')"
    );
    $stmt->execute([$barberId, $date]);

    foreach ($stmt->fetchAll() as $appointment) {
        if (ranges_overlap(
            $start,
            $end,
            time_to_minutes($appointment['start_time']),
            time_to_minutes($appointment['end_time'])
        )) {
            return false;
        }
    }

    return true;
}

function available_slots(PDO $pdo, int $serviceId, int $barberId, string $date): array
{
    $serviceStmt = $pdo->prepare('SELECT duration_minutes FROM services WHERE id = ? AND active = 1');
    $serviceStmt->execute([$serviceId]);
    $service = $serviceStmt->fetch();
    if (!$service) {
        return [];
    }

    $sql = "SELECT b.* FROM barbers b
            INNER JOIN barber_services bs ON bs.barber_id = b.id
            WHERE b.active = 1 AND bs.service_id = ?";
    $params = [$serviceId];

    if ($barberId > 0) {
        $sql .= ' AND b.id = ?';
        $params[] = $barberId;
    }

    $sql .= ' ORDER BY b.name';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $barbers = $stmt->fetchAll();

    $result = [];
    foreach ($barbers as $barber) {
        $start = time_to_minutes($barber['work_start']);
        $endLimit = time_to_minutes($barber['work_end']) - (int) $service['duration_minutes'];

        for ($minute = $start; $minute <= $endLimit; $minute += 15) {
            $time = minutes_to_time($minute);
            if (!slot_is_available($pdo, (int) $barber['id'], $serviceId, $date, $time)) {
                continue;
            }

            $key = $barberId > 0 ? $barber['id'] . '-' . $time : $time;
            if (!isset($result[$key])) {
                $result[$key] = [
                    'time' => $time,
                    'barber_id' => (int) $barber['id'],
                    'barber_name' => $barber['name'],
                ];
            }
        }
    }

    usort($result, fn (array $a, array $b): int => strcmp($a['time'], $b['time']));
    return array_values($result);
}
