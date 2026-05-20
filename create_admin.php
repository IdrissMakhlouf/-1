<?php
// create_admin.php - ملف لإنشاء مستخدم Admin جديد
require_once 'config.php';
require_once 'functions.php';

// بيانات المسؤول الجديد
$username = 'admin';
$email = 'admin@heritage.dz';
$password = 'admin123'; // غيرها لكلمة مرور أقوى
$role = 'admin';

// تشفير كلمة المرور
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// التحقق من عدم وجود مستخدم بنفس الاسم
$check = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
$check->bind_param("ss", $username, $email);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    // تحديث كلمة المرور للمستخدم الموجود
    $update = $conn->prepare("UPDATE users SET password_hash = ? WHERE username = ?");
    $update->bind_param("ss", $password_hash, $username);
    
    if ($update->execute()) {
        echo "✅ تم تحديث كلمة المرور للمسؤول بنجاح!<br>";
        echo "📧 اسم المستخدم: admin<br>";
        echo "🔑 كلمة المرور: admin123<br>";
        echo "⚠️ يرجى تغيير كلمة المرور بعد تسجيل الدخول لأول مرة.";
    } else {
        echo "❌ حدث خطأ: " . $conn->error;
    }
} else {
    // إضافة مستخدم جديد
    $insert = $conn->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
    $insert->bind_param("ssss", $username, $email, $password_hash, $role);
    
    if ($insert->execute()) {
        echo "✅ تم إنشاء حساب المسؤول بنجاح!<br>";
        echo "📧 اسم المستخدم: admin<br>";
        echo "🔑 كلمة المرور: admin123<br>";
        echo "🔗 <a href='login.php'>الذهاب إلى صفحة تسجيل الدخول</a>";
    } else {
        echo "❌ حدث خطأ: " . $conn->error;
    }
}

// إغلاق الاتصال
$conn->close();
?>