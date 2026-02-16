<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
include __DIR__ . '/../includes/header.php';
$page_title = 'الصفوف';
?>
<main class="container section-padding">
    <h2 class="mb-4">الصفوف والمناهج</h2>
    <p class="text-white">عرض الصفوف، المواد، والروابط المقررة لكل الصف.</p>

    <div class="card auth-card mt-3">
        <div class="card-body">
            <?php
            $pdo = DB::conn();
            $sql = 'SELECT c.id, c.name_ar AS class_name, c.room_label, s.name_ar AS stage_name, IFNULL(e.cnt,0) AS enrolled_count
                    FROM school_classes c
                    JOIN school_stages s ON s.id = c.stage_id
                    LEFT JOIN (SELECT class_id, COUNT(*) AS cnt FROM school_enrollments GROUP BY class_id) e ON e.class_id = c.id
                    ORDER BY s.id, c.name_ar';
            $rows = $pdo->query($sql)->fetchAll();
            ?>

            <?php if (empty($rows)): ?>
                <p class="helper-text">لا توجد صفوف مُسجّلة حالياً.</p>
            <?php else: ?>
                <div class="table-responsive mt-3">
                    <table class="table table-dark">
                        <thead>
                            <tr>
                                <th>المرحلة</th>
                                <th>الصف</th>
                                <th>القاعة</th>
                                <th>عدد الطلاب</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $r): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($r['stage_name']); ?></td>
                                    <td><?php echo htmlspecialchars($r['class_name']); ?></td>
                                    <td><?php echo htmlspecialchars($r['room_label'] ?? '-'); ?></td>
                                    <td><?php echo (int)$r['enrolled_count']; ?></td>
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
