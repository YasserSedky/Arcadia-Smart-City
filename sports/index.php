<?php
$page_title = 'النادي الرياضي';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$pdo = DB::conn();

// Get stats
$stats = $pdo->query('SELECT 
    (SELECT COUNT(*) FROM sports_facilities WHERE status="available") as available_facilities,
    (SELECT COUNT(*) FROM sports_activities WHERE status="active" AND starts_at > NOW()) as active_activities,
    (SELECT COUNT(DISTINCT user_id) FROM sports_registrations WHERE status="active") as active_members
')->fetch();

// Get facilities grouped by type
$stmt = $pdo->query('SELECT 
        f.id,
        f.type_id,
        f.name_ar,
        f.capacity,
        f.price_per_hour,
        f.description,
        f.features,
        f.requirements,
        f.status,
        t.name_ar as type_name,
        t.icon
    FROM sports_facilities f
    JOIN sports_facility_types t ON t.id = f.type_id
    WHERE f.status = "available"
    ORDER BY t.name_ar ASC, f.name_ar ASC');

$facilities = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $facilities[$row['type_name']][] = $row;
}

// Get upcoming activities with registration count
$activities = $pdo->query('SELECT 
        a.*,
        f.name_ar as facility_name,
        (SELECT COUNT(*) FROM sports_registrations 
         WHERE activity_id = a.id 
         AND status IN ("active", "pending")) as registered_count
    FROM sports_activities a
    JOIN sports_facilities f ON f.id = a.facility_id
    WHERE a.status IN ("active", "draft")
    AND (a.registration_closes_at > NOW() OR a.registration_closes_at IS NULL)
    AND a.starts_at > NOW()
    ORDER BY a.starts_at ASC, a.status DESC
    LIMIT 6')->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<main class="container section-padding">
    <!-- Stats -->
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="bi bi-grid-3x3-gap display-4"></i>
                    <h5 class="mt-3">المرافق المتاحة</h5>
                    <h3 class="fw-bold text-gradient"><?= $stats['available_facilities'] ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="bi bi-calendar2-check display-4"></i>
                    <h5 class="mt-3">الأنشطة الحالية</h5>
                    <h3 class="fw-bold text-gradient"><?= $stats['active_activities'] ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="bi bi-people display-4"></i>
                    <h5 class="mt-3">الأعضاء النشطين</h5>
                    <h3 class="fw-bold text-gradient"><?= $stats['active_members'] ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Facilities -->
    <section class="facilities-section">
        <h4 class="mb-4">المرافق الرياضية</h4>
        <?php
        if (empty($facilities)):
        ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                لا توجد مرافق متاحة حالياً
            </div>
            <?php
        else:
            foreach ($facilities as $type_name => $type_facilities):
            ?>
                <h5 class="mt-4 mb-3">
                    <?= htmlspecialchars($type_name) ?>
                </h5>
                <div class="row g-4 mb-4">
                    <?php foreach ($type_facilities as $facility):
                        $facilityId = (int)$facility['id'];
                        $capacity = (int)$facility['capacity'];
                        $price = (float)$facility['price_per_hour'];
                        $icon = htmlspecialchars($facility['icon'] ?? 'grid');
                        $name = htmlspecialchars($facility['name_ar']);
                        $description = $facility['description'] ? htmlspecialchars($facility['description']) : '';
                    ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card facility-card shadow-hover h-100">
                                <div class="card-body d-flex flex-column">
                                    <!-- Facility Header -->
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="flex-shrink-0">
                                            <i class="bi bi-<?= $icon ?> display-6 text-gradient"></i>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h5 class="card-title mb-1"><?= $name ?></h5>
                                            <div class="text-muted small">
                                                <i class="bi bi-people me-1"></i>
                                                السعة: <?= $capacity ?> شخص
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Facility Description -->
                                    <?php if ($description): ?>
                                        <p class="facility-description mb-3">
                                            <?= nl2br($description) ?>
                                        </p>
                                    <?php endif; ?>

                                    <!-- Features -->
                                    <?php if (!empty($facility['features'])):
                                        $features = json_decode($facility['features'], true);
                                        if ($features && is_array($features)):
                                            $featureLabels = [
                                                'lighting' => 'إضاءة',
                                                'showers' => 'غرف استحمام',
                                                'lockers' => 'خزائن',
                                                'parking' => 'موقف سيارات',
                                                'heated' => 'تدفئة',
                                                'indoorPool' => 'مغطى',
                                                'lifeguard' => 'منقذ',
                                                'cardioArea' => 'منطقة كارديو',
                                                'weightsArea' => 'منطقة أوزان',
                                                'trainers' => 'مدربين',
                                                'waterDispenser' => 'براد ماء',
                                                'equipment' => 'معدات',
                                                'seating' => 'مقاعد',
                                                'scoreboard' => 'لوحة نتائج',
                                                'airConditioning' => 'تكييف',
                                                'privateTrainer' => 'مدرب خاص'
                                            ];
                                    ?>
                                            <div class="facility-features mb-3">
                                                <div class="row g-2">
                                                    <?php foreach ($features as $feature => $value):
                                                        if ($value === true):
                                                            $label = $featureLabels[strtolower($feature)] ?? $feature;
                                                    ?>
                                                            <div class="col-auto">
                                                                <span class="badge bg-light text-dark">
                                                                    <i class="bi bi-check2-circle me-1 text-success"></i>
                                                                    <?= htmlspecialchars($label) ?>
                                                                </span>
                                                            </div>
                                                    <?php
                                                        endif;
                                                    endforeach;
                                                    ?>
                                                </div>
                                            </div>
                                    <?php
                                        endif;
                                    endif;
                                    ?>

                                    <!-- Booking Footer -->
                                    <div class="mt-auto">
                                        <?php if (!empty($facility['requirements'])): ?>
                                            <div class="requirements-note small text-muted mb-2">
                                                <i class="bi bi-info-circle me-1"></i>
                                                <?= htmlspecialchars($facility['requirements']) ?>
                                            </div>
                                        <?php endif; ?>

                                        <div class="d-flex align-items-center justify-content-between">
                                            <span class="price-tag text-gradient">
                                                <?= number_format($price) ?> جنيه/ساعة
                                            </span>
                                            <a href="bookings.php?facility_id=<?= $facilityId ?>"
                                                class="btn btn-sm btn-outline-primary book-btn">
                                                <i class="bi bi-calendar2-plus me-1"></i>
                                                حجز المرفق
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
        <?php
            endforeach;
        endif;
        ?>
    </section>

    <!-- Activities -->
    <section class="activities-section mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="m-0">الأنشطة والبرامج القادمة</h4>
            <a href="activities.php" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-grid me-1"></i>
                عرض كل الأنشطة
            </a>
        </div>

        <?php if (empty($activities)): ?>
            <div class="alert alert-info d-flex align-items-center">
                <i class="bi bi-info-circle-fill me-2"></i>
                لا توجد أنشطة قادمة حالياً
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($activities as $activity):
                    $startDate = new DateTime($activity['starts_at']);
                    $endDate = !empty($activity['ends_at']) ? new DateTime($activity['ends_at']) : null;
                    $registrationCloses = !empty($activity['registration_closes_at']) ? new DateTime($activity['registration_closes_at']) : null;
                    $now = new DateTime();

                    // حساب حالة النشاط
                    $isRegistrationClosed = $registrationCloses && $now > $registrationCloses;
                    $isFull = $activity['status'] === 'full';
                    $availableSpots = max(0, intval($activity['capacity']) - intval($activity['registered_count'] ?? 0));
                ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card activity-card h-100 shadow-hover">
                            <div class="card-body d-flex flex-column">
                                <div class="activity-header mb-3">
                                    <h5 class="card-title text-primary mb-1">
                                        <?= htmlspecialchars($activity['name_ar']) ?>
                                    </h5>
                                    <h6 class="facility-name">
                                        <i class="bi bi-geo-alt me-1"></i>
                                        <?= htmlspecialchars($activity['facility_name']) ?>
                                    </h6>
                                </div>

                                <div class="activity-details">
                                    <?php if ($activity['instructor_name']): ?>
                                        <div class="detail-item">
                                            <i class="bi bi-person-badge me-2"></i>
                                            <span>المدرب:</span>
                                            <strong><?= htmlspecialchars($activity['instructor_name']) ?></strong>
                                        </div>
                                    <?php endif; ?>

                                    <div class="detail-item">
                                        <i class="bi bi-calendar3 me-2"></i>
                                        <span>يبدأ في:</span>
                                        <strong><?= $startDate->format('d/m/Y') ?></strong>
                                    </div>

                                    <?php if ($activity['schedule']): ?>
                                        <div class="detail-item">
                                            <i class="bi bi-clock me-2"></i>
                                            <span>المواعيد:</span>
                                            <strong><?= htmlspecialchars($activity['schedule']) ?></strong>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($activity['level'] && $activity['level'] !== 'all'): ?>
                                        <div class="detail-item">
                                            <i class="bi bi-bar-chart me-2"></i>
                                            <span>المستوى:</span>
                                            <strong>
                                                <?= [
                                                    'beginner' => 'مبتدئ',
                                                    'intermediate' => 'متوسط',
                                                    'advanced' => 'متقدم'
                                                ][$activity['level']] ?? $activity['level'] ?>
                                            </strong>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($activity['age_min']) || !empty($activity['age_max'])): ?>
                                        <div class="detail-item">
                                            <i class="bi bi-person me-2"></i>
                                            <span>العمر:</span>
                                            <strong>
                                                <?php
                                                if (!empty($activity['age_min']) && !empty($activity['age_max'])) {
                                                    echo "{$activity['age_min']} - {$activity['age_max']} سنة";
                                                } elseif (!empty($activity['age_min'])) {
                                                    echo "أكبر من {$activity['age_min']} سنة";
                                                } elseif (!empty($activity['age_max'])) {
                                                    echo "أقل من {$activity['age_max']} سنة";
                                                }
                                                ?>
                                            </strong>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <?php if ($activity['description']): ?>
                                    <p class="activity-description mt-2 mb-3">
                                        <?= nl2br(htmlspecialchars($activity['description'])) ?>
                                    </p>
                                <?php endif; ?>

                                <div class="activity-footer mt-auto">
                                    <?php if ($availableSpots > 0 && !$isRegistrationClosed): ?>
                                        <div class="spots-left text-success small mb-2">
                                            <i class="bi bi-person-check me-1"></i>
                                            متبقي <?= $availableSpots ?> مقعد
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($registrationCloses && $now < $registrationCloses): ?>
                                        <div class="registration-deadline text-warning small mb-2">
                                            <i class="bi bi-clock-history me-1"></i>
                                            آخر موعد للتسجيل: <?= $registrationCloses->format('d/m/Y') ?>
                                        </div>
                                    <?php endif; ?>

                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="price-tag text-gradient">
                                            <?= number_format($activity['price']) ?> جنيه
                                        </div>

                                        <?php if ($isFull): ?>
                                            <span class="badge bg-secondary">مكتمل</span>
                                        <?php elseif ($isRegistrationClosed): ?>
                                            <span class="badge bg-danger">انتهى التسجيل</span>
                                        <?php else: ?>
                                            <a href="activities.php?register=<?= intval($activity['id']) ?>"
                                                class="btn btn-sm btn-gradient">
                                                <i class="bi bi-person-plus me-1"></i>
                                                تسجيل
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- My Bookings Button -->
    <div class="text-center mt-5">
        <a href="mybookings.php" class="btn btn-gradient">
            <i class="bi bi-calendar2-week me-2"></i>
            حجوزاتي
        </a>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>