<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
include __DIR__ . '/../includes/header.php';
$page_title = 'المعلمين';
?>
<main class="container section-padding">
    <h2 class="mb-4">قائمة المعلمين</h2>
    <p class="text-white">قائمة أعضاء هيئة التدريس ومعلومات الاتصال والتخصصات.</p>

    <div class="card auth-card mt-3">
        <div class="card-body">
            <?php
            $pdo = DB::conn();
            $rows = $pdo->query('SELECT t.*, u.full_name, u.email FROM school_teachers t JOIN users u ON u.id = t.user_id ORDER BY u.full_name')->fetchAll();
            ?>

            <?php if (empty($rows)): ?>
                <p class="helper-text">لا يوجد أعضاء هيئة تدريس مُسجّلين بعد.</p>
            <?php else: ?>
                <div class="table-responsive mt-3">
                    <table class="table table-dark">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>الاسم</th>
                                <th>التخصص</th>
                                <th>البريد</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $i => $r): ?>
                                <tr>
                                    <td><?php echo $i + 1; ?></td>
                                    <td><?php echo htmlspecialchars($r['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($r['specialty'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($r['email'] ?? '-'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php';
