<?php
$page_title = 'الطلاب';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
if (!user_can(['super_admin', 'school_admin'])) {
  redirect('/dashboard/index.php');
}
$pdo = DB::conn();

if (isset($_POST['__action']) && $_POST['__action'] === 'add_student') {
  $name = trim($_POST['full_name'] ?? '');
  $guardian = trim($_POST['guardian_phone'] ?? '');
  $dob = $_POST['date_of_birth'] ?? null;
  $gender = $_POST['gender'] ?? null;
  if ($name !== '') {
    $stmt = $pdo->prepare('INSERT INTO school_students(full_name, guardian_phone, date_of_birth, gender) VALUES(?,?,?,?)');
    try {
      $stmt->execute([$name, $guardian ?: null, $dob ?: null, $gender ?: null]);
    } catch (PDOException $e) {
    }
  }
  redirect('/dashboard/school/students.php');
}

if (isset($_POST['__action']) && $_POST['__action'] === 'enroll') {
  $student_id = (int)($_POST['student_id'] ?? 0);
  $class_id = (int)($_POST['class_id'] ?? 0);
  $year = trim($_POST['year'] ?? '');
  if ($student_id > 0 && $class_id > 0 && $year !== '') {
    $stmt = $pdo->prepare('INSERT INTO school_enrollments(student_id, class_id, year) VALUES(?,?,?)');
    try {
      $stmt->execute([$student_id, $class_id, $year]);
    } catch (PDOException $e) {
    }
  }
  redirect('/dashboard/school/students.php');
}

$students = $pdo->query('SELECT * FROM school_students ORDER BY full_name')->fetchAll();
$classes = $pdo->query('SELECT c.id, CONCAT(s.name_ar," - ",c.name_ar) AS name FROM school_classes c JOIN school_stages s ON s.id=c.stage_id ORDER BY s.id, c.name_ar')->fetchAll();
$enrolls = $pdo->query('SELECT e.*, st.full_name, CONCAT(ss.name_ar, " - ", sc.name_ar) AS class_name FROM school_enrollments e JOIN school_students st ON st.id=e.student_id JOIN school_classes sc ON sc.id=e.class_id JOIN school_stages ss ON ss.id=sc.stage_id ORDER BY e.year DESC')->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<main class="container section-padding">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">الطلاب</h3>
    <a href="<?php echo APP_BASE; ?>/dashboard/school/index.php" class="btn btn-outline-light">رجوع</a>
  </div>

  <div class="feature-card mb-4">
    <h5 class="mb-3">إضافة طالب</h5>
    <form method="post" class="row g-3">
      <input type="hidden" name="__action" value="add_student">
      <div class="col-md-4"><label class="form-label">الاسم الكامل</label><input class="form-control" name="full_name" required></div>
      <div class="col-md-3"><label class="form-label">هاتف ولي الأمر</label><input class="form-control" name="guardian_phone"></div>
      <div class="col-md-2"><label class="form-label">الميلاد</label><input type="date" class="form-control" name="date_of_birth"></div>
      <div class="col-md-2"><label class="form-label">النوع</label>
        <select name="gender" class="form-select">
          <option value="">—</option>
          <option value="male">ذكر</option>
          <option value="female">أنثى</option>
        </select>
      </div>
      <div class="col-md-1 d-grid"><label class="form-label">&nbsp;</label><button class="btn btn-gradient" type="submit">إضافة</button></div>
    </form>
  </div>

  <div class="feature-card mb-4">
    <h5 class="mb-3">تسجيل طالب في فصل</h5>
    <form method="post" class="row g-3">
      <input type="hidden" name="__action" value="enroll">
      <div class="col-md-4"><label class="form-label">الطالب</label>
        <select name="student_id" class="form-select" required>
          <option value="">اختر</option>
          <?php foreach ($students as $s): ?><option value="<?php echo (int)$s['id']; ?>"><?php echo htmlspecialchars($s['full_name']); ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4"><label class="form-label">الفصل</label>
        <select name="class_id" class="form-select" required>
          <option value="">اختر</option>
          <?php foreach ($classes as $c): ?><option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2"><label class="form-label">السنة</label><input class="form-control" name="year" placeholder="2025/2026" required></div>
      <div class="col-md-2 d-grid"><label class="form-label">&nbsp;</label><button class="btn btn-gradient" type="submit">تسجيل</button></div>
    </form>
  </div>

  <div class="table-responsive feature-card">
    <table class="table table-dark table-striped align-middle mb-0">
      <thead>
        <tr>
          <th>السنة</th>
          <th>الطالب</th>
          <th>الفصل</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($enrolls as $e): ?>
          <tr>
            <td><?php echo htmlspecialchars($e['year']); ?></td>
            <td><?php echo htmlspecialchars($e['full_name']); ?></td>
            <td><?php echo htmlspecialchars($e['class_name']); ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($enrolls)): ?><tr>
            <td colspan="3" class="text-center text-muted">لا توجد تسجيلات</td>
          </tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
