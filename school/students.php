<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
include __DIR__ . '/../includes/header.php';
$page_title = 'الطلاب';
?>
<main class="container section-padding">
    <h2 class="mb-4">سجل الطلاب</h2>
    <p class="text-white">قائمة الطلاب المسجلة في المدرسة وإمكانية عرض تفاصيل كل طالب.</p>

    <div class="card auth-card mt-3">
        <div class="card-body">
            <?php
            // Show only students whose guardian (by phone) is registered in the same residential unit
            $pdo = DB::conn();
            $unitId = $_SESSION['user']['unit_id'] ?? null;
            $students = [];
            if ($unitId) {
                $stmt = $pdo->prepare('SELECT s.* FROM school_students s JOIN users u ON u.phone = s.guardian_phone WHERE u.unit_id = ? ORDER BY s.full_name');
                $stmt->execute([(int)$unitId]);
                $students = $stmt->fetchAll();
            }
            ?>

            <?php if (empty($students)): ?>
                <p class="helper-text">لم يتم العثور على طلاب يقيمون في نفس وحدتك السكنية.</p>
            <?php else: ?>
                <div class="table-responsive mt-3">
                    <table class="table table-dark">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>الاسم</th>
                                <th>الصف الملتحق به</th>
                                <th>تاريخ الميلاد</th>
                                <th>هاتف ولي الأمر</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $i => $s):
                                // find latest enrollment/class name for the student if exists
                                $cls = $pdo->prepare('SELECT sc.name_ar AS class_name, ss.name_ar AS stage_name FROM school_enrollments e JOIN school_classes sc ON sc.id=e.class_id JOIN school_stages ss ON ss.id=sc.stage_id WHERE e.student_id = ? ORDER BY e.year DESC LIMIT 1');
                                $cls->execute([(int)$s['id']]);
                                $c = $cls->fetch();
                                $classLabel = $c ? ($c['stage_name'] . ' - ' . $c['class_name']) : '-';
                            ?>
                                <tr>
                                    <td><?php echo $i + 1; ?></td>
                                    <td><?php echo htmlspecialchars($s['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($classLabel); ?></td>
                                    <td><?php echo htmlspecialchars($s['date_of_birth'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($s['guardian_phone'] ?? ''); ?></td>
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
