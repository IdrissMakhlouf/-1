<?php
/**
 * AI API Endpoint - Handles DeepSeek API Requests
 * Heritage Platform - Algeria Cultural Heritage
 */

require_once '../config/db.php';

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get request body
$input = json_decode(file_get_contents('php://input'), true);
$question = isset($input['question']) ? trim($input['question']) : '';

if (empty($question)) {
    echo json_encode(['success' => false, 'message' => 'الرجاء إدخال سؤال']);
    exit();
}

// DeepSeek API Configuration
define('DEEPSEEK_API_KEY', 'sk-a17db704b2ca46c38d317000000de41e7'); // استبدل بمفتاح API الخاص بك
define('DEEPSEEK_API_URL', 'https://api.deepseek.com/v1/chat/completions');

// Prepare system prompt
$system_prompt = "أنت مؤرخ متخصص في التاريخ والتراث الجزائري. لديك معرفة عميقة بكل العصور التاريخية للجزائر:
- العصر القديم: الفينيقيون، النوميديون (ماسينيسا، يوغرطة، يوبا)، الرومان، الوندال، البيزنطيون
- العصر الإسلامي: الفتح الإسلامي، الأغالبة، الفاطميون، الزيريون، الحماديون، المرابطون، الموحدون، الزيانيون
- العصر العثماني: 1515-1830، البايلك، الداي
- فترة الاستعمار الفرنسي: 1830-1962، المقاومة الشعبية
- ثورة التحرير الجزائرية: 1954-1962، شخصيات الثورة
- الجزائر المستقلة: 1962- حتى الآن

قم بتقديم إجابات دقيقة، مفصلة، وموثقة تاريخياً. استخدم لغة عربية فصيحة وواضحة. اذكر التواريخ المهمة والأسماء.
إذا كان السؤال خارج نطاق التاريخ الجزائري، أجب بلطف أن تخصصك هو التاريخ الجزائري فقط.";

// Prepare API request data
$data = [
    'model' => 'deepseek-chat',
    'messages' => [
        ['role' => 'system', 'content' => $system_prompt],
        ['role' => 'user', 'content' => $question]
    ],
    'temperature' => 0.7,
    'max_tokens' => 2000,
    'stream' => false
];

// Send request to DeepSeek API
$ch = curl_init(DEEPSEEK_API_URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . DEEPSEEK_API_KEY
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

if ($curl_error) {
    echo json_encode(['success' => false, 'message' => 'خطأ في الاتصال: ' . $curl_error]);
    exit();
}

if ($http_code == 200 && $response) {
    $result = json_decode($response, true);
    if (isset($result['choices'][0]['message']['content'])) {
        echo json_encode([
            'success' => true,
            'response' => $result['choices'][0]['message']['content']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'لم يتم الحصول على رد من المساعد']);
    }
} else {
    // إذا فشل الاتصال بـ DeepSeek، استخدم ردود محلية كبديل
    $localResponse = getLocalResponse($question);
    echo json_encode([
        'success' => true,
        'response' => $localResponse
    ]);
}
exit();

/**
 * دالة ردود محلية كبديل عند عدم توفر API
 */
function getLocalResponse($question) {
    $responses = [
        'ماسينيسا' => 'ماسينيسا (238 ق.م - 148 ق.م) هو ملك نوميدي عظيم وحد القبائل النوميدية وأسس مملكة نوميديا الموحدة. كان حليفاً لروما في الحرب البونيقية الثانية، وعمل على نشر الزراعة والحضارة في شمال أفريقيا.',
        'ثورة التحرير' => 'اندلعت ثورة التحرير الجزائرية في 1 نوفمبر 1954 بقيادة جبهة التحرير الوطني. استمرت الثورة 7 سنوات ونصف، وأسفرت عن استقلال الجزائر في 5 يوليو 1962. من أبرز قادتها: مصالي الحاج، العربي بن مهيدي، كريم بلقاسم، هواري بومدين، ومحمد بوضياف.',
        'جوليا' => 'قسنطينة (عاصمة الشرق الجزائري) أسسها الفينيقيون ثم الرومان وأسموها سيرتا، ثم سميت قسنطينة نسبة للإمبراطور قسطنطين. تتميز بجسر الحريرة المعلق وقصر أحمد باي ومسجد الأمير عبد القادر.',
        'default' => 'شكراً لسؤالك. أنا هنا للإجابة عن أسئلة التاريخ والتراث الجزائري. هل يمكنك إعادة صياغة السؤال بشكل أكثر تحديداً؟'
    ];
    
    foreach ($responses as $key => $response) {
        if (strpos($question, $key) !== false) {
            return $response;
        }
    }
    return $responses['default'];
}
