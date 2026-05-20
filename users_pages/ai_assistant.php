<?php
/**
 * AI Historical Assistant Page - Powered by DeepSeek API (AJAX Version)
 * Heritage Platform - Algeria Cultural Heritage
 */

require_once '../config/db.php';

// Check if user is logged in
Auth::requireLogin();

// DeepSeek API Configuration
define('DEEPSEEK_API_KEY', 'sk-a17db704b2ca46c38d317326d8000001e7'); // استبدل بمفتاح API الخاص بك
define('DEEPSEEK_API_URL', 'https://api.deepseek.com/v1/chat/completions');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>المساعد الذكي للتاريخ الجزائري - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        * { font-family: 'Cairo', sans-serif; }
        body { background: linear-gradient(135deg, #1a2634 0%, #2c3e50 100%); direction: rtl; min-height: 100vh; }
        
        /* Navbar */
        .navbar { background: rgba(44, 62, 80, 0.95); backdrop-filter: blur(10px); box-shadow: 0 2px 15px rgba(0,0,0,0.1); }
        .navbar-brand { font-weight: 700; }
        .navbar-brand i { color: #e67e22; }
        
        /* Main Layout */
        .main-wrapper {
            display: flex;
            gap: 25px;
            margin-top: 90px;
            margin-bottom: 50px;
        }
        .sidebar-col { flex: 0 0 300px; }
        .content-col { flex: 1; }
        
        /* Hero Section */
        .ai-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 25px;
            padding: 30px;
            margin-bottom: 30px;
            color: white;
            position: relative;
            overflow: hidden;
        }
        .ai-hero h1 { font-size: 2rem; font-weight: 800; margin-bottom: 10px; }
        .ai-hero p { font-size: 1rem; opacity: 0.9; }
        
        /* Chat Container */
        .chat-container {
            background: white;
            border-radius: 25px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
        }
        
        /* Chat Messages */
        .chat-messages {
            flex: 1;
            padding: 25px;
            height: 500px;
            overflow-y: auto;
            background: #f8f9fa;
        }
        
        /* Message Bubbles */
        .message {
            display: flex;
            margin-bottom: 20px;
            animation: fadeInUp 0.3s ease;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .message.user { justify-content: flex-end; }
        .message.ai { justify-content: flex-start; }
        
        .message-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 10px;
            flex-shrink: 0;
        }
        .message.user .message-avatar { background: #e67e22; color: white; order: 1; }
        .message.ai .message-avatar { background: #8e44ad; color: white; }
        
        .message-content {
            max-width: 70%;
            padding: 12px 18px;
            border-radius: 18px;
            line-height: 1.6;
            font-size: 0.95rem;
        }
        .message.user .message-content {
            background: #e67e22;
            color: white;
            border-radius: 18px 18px 5px 18px;
        }
        .message.ai .message-content {
            background: white;
            color: #2c3e50;
            border-radius: 18px 18px 18px 5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        /* Typing Indicator */
        .typing-indicator {
            display: flex;
            gap: 5px;
            padding: 10px 15px;
            background: white;
            border-radius: 18px;
            width: fit-content;
        }
        .typing-dot {
            width: 8px;
            height: 8px;
            background: #999;
            border-radius: 50%;
            animation: typing 1.4s infinite;
        }
        .typing-dot:nth-child(2) { animation-delay: 0.2s; }
        .typing-dot:nth-child(3) { animation-delay: 0.4s; }
        @keyframes typing {
            0%, 60%, 100% { transform: translateY(0); opacity: 0.5; }
            30% { transform: translateY(-8px); opacity: 1; }
        }
        
        /* Input Area */
        .chat-input-area {
            padding: 20px;
            background: white;
            border-top: 1px solid #eee;
        }
        .input-group-custom {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .chat-input {
            flex: 1;
            border: 2px solid #e0e0e0;
            border-radius: 50px;
            padding: 12px 20px;
            resize: none;
            font-size: 0.95rem;
            transition: all 0.3s;
        }
        .chat-input:focus {
            border-color: #e67e22;
            outline: none;
            box-shadow: 0 0 0 3px rgba(230,126,34,0.1);
        }
        .send-btn {
            background: #e67e22;
            color: white;
            border: none;
            border-radius: 50px;
            padding: 12px 25px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .send-btn:hover {
            background: #d35400;
            transform: translateY(-2px);
        }
        .send-btn:disabled {
            background: #95a5a6;
            cursor: not-allowed;
            transform: none;
        }
        
        /* Suggested Questions */
        .suggestions-section {
            padding: 15px 20px;
            background: #f8f9fa;
            border-top: 1px solid #eee;
        }
        .suggestion-btn {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 50px;
            padding: 6px 15px;
            margin: 4px;
            font-size: 0.8rem;
            transition: all 0.3s;
            cursor: pointer;
            display: inline-block;
        }
        .suggestion-btn:hover {
            background: #e67e22;
            color: white;
            border-color: #e67e22;
        }
        
        /* Scrollbar */
        .chat-messages::-webkit-scrollbar { width: 5px; }
        .chat-messages::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
        .chat-messages::-webkit-scrollbar-thumb { background: #e67e22; border-radius: 10px; }
        
        /* Responsive */
        @media (max-width: 992px) {
            .main-wrapper { flex-direction: column; }
            .sidebar-col { flex: auto; }
        }
        @media (max-width: 576px) {
            .ai-hero h1 { font-size: 1.3rem; }
            .message-content { max-width: 85%; font-size: 0.85rem; }
            .message-avatar { width: 32px; height: 32px; font-size: 0.8rem; }
            .suggestion-btn { font-size: 0.7rem; }
        }
        
        footer { background: #1a2634; color: white; padding: 30px 0 20px; margin-top: 50px; }
        
        /* Logo Animation */
        .logo-animation {
            transition: all 0.3s;
        }
        .logo-animation:hover {
            transform: scale(1.05);
        }
    </style>
</head>
<body>

<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container">
        <a class="navbar-brand" href="../index.php">
            <i class="fas fa-landmark"></i> <?= SITE_NAME ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="../index.php">الرئيسية</a></li>
                <li class="nav-item"><a class="nav-link" href="explore.php">استكشاف</a></li>
                <li class="nav-item"><a class="nav-link" href="lessons.php">التعليم</a></li>
                <li class="nav-item"><a class="nav-link" href="archive.php">الأرشيف</a></li>
                <li class="nav-item"><a class="nav-link" href="smart_trips.php">رحلات ذكية</a></li>
                <li class="nav-item"><a class="nav-link active" href="ai_assistant.php">
                    <i class="fas fa-robot"></i> المساعد الذكي
                </a></li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['username']) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> لوحة التحكم</a></li>
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user"></i> ملفي الشخصي</a></li>
                        <li><a class="dropdown-item" href="my_trips.php"><i class="fas fa-route"></i> رحلاتي</a></li>
                        <?php if (Auth::isAdmin()): ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../admin/index.php"><i class="fas fa-tachometer-alt"></i> لوحة التحكم (مدير)</a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt"></i> تسجيل خروج</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container">
    <div class="main-wrapper">
        <!-- Sidebar -->
        <div class="sidebar-col">
            <?php include 'menu.php'; ?>
        </div>
        
        <!-- Main Content -->
        <div class="content-col">
            <!-- Hero Section -->
            <div class="ai-hero" data-aos="fade-up">
                <div class="row align-items-center">
                    <div class="col-md-9">
                        <h1><i class="fas fa-robot"></i> المساعد الذكي للتاريخ الجزائري</h1>
                        <p>اسألني أي شيء عن تاريخ وتراث الجزائر - إجابات فورية بالتفصيل</p>
                        <div class="mt-2">
                            <span class="badge bg-light text-dark me-2"><i class="fas fa-microchip"></i> DeepSeek AI</span>
                            <span class="badge bg-light text-dark"><i class="fas fa-history"></i> معلومات موثقة</span>
                        </div>
                    </div>
                    <div class="col-md-3 text-center logo-animation">
                        <i class="fas fa-landmark fa-4x"></i>
                    </div>
                </div>
            </div>
            
            <!-- Chat Container -->
            <div class="chat-container" data-aos="fade-up" data-aos-delay="100">
                <!-- Chat Messages Area -->
                <div class="chat-messages" id="chatMessages">
                    <!-- Welcome Message -->
                    <div class="message ai">
                        <div class="message-avatar">
                            <i class="fas fa-robot"></i>
                        </div>
                        <div class="message-content">
                            <strong>مرحباً بك في المساعد الذكي للتاريخ الجزائري! 🇩🇿</strong><br><br>
                            أنا هنا للإجابة على جميع استفساراتك حول تاريخ وتراث الجزائر العريق.<br><br>
                            يمكنك أن تسألني عن:
                            <ul class="mt-2">
                                <li>العصور التاريخية والحضارات (فينيقيون، نوميديون، رومان...)</li>
                                <li>الشخصيات التاريخية البارزة</li>
                                <li>المعارك والأحداث المهمة</li>
                                <li>المواقع الأثرية والتاريخية</li>
                                <li>الثورة التحريرية وشخصياتها</li>
                                <li>التاريخ الحديث للجزائر</li>
                            </ul>
                            <strong class="mt-2 d-block">اكتب سؤالك في الأسفل وسأجيبك بالتفصيل! 📚</strong>
                        </div>
                    </div>
                </div>
                
                <!-- Input Area -->
                <div class="chat-input-area">
                    <div class="input-group-custom">
                        <textarea id="questionInput" class="chat-input" rows="1" 
                                  placeholder="اكتب سؤالك هنا عن التاريخ الجزائري..."></textarea>
                        <button id="sendBtn" class="send-btn">
                            <i class="fas fa-paper-plane"></i> إرسال
                        </button>
                    </div>
                </div>
                
                <!-- Suggested Questions -->
                <div class="suggestions-section">
                    <p class="text-muted mb-1"><i class="fas fa-lightbulb"></i> أسئلة مقترحة:</p>
                    <div id="suggestionsContainer">
                        <!-- سيتم تحميل الأسئلة المقترحة عن طريق JavaScript -->
                    </div>
                </div>
            </div>
            
            <!-- Info Cards -->
            <div class="row mt-4 g-3" data-aos="fade-up" data-aos-delay="200">
                <div class="col-md-4">
                    <div class="bg-white rounded-3 p-3 text-center h-100">
                        <i class="fas fa-book-open fa-2x text-primary mb-2 d-block"></i>
                        <h6 class="mb-1">معلومات موثقة</h6>
                        <p class="small text-muted mb-0">معلومات دقيقة من مصادر تاريخية</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="bg-white rounded-3 p-3 text-center h-100">
                        <i class="fas fa-brain fa-2x text-success mb-2 d-block"></i>
                        <h6 class="mb-1">ذكاء اصطناعي</h6>
                        <p class="small text-muted mb-0">مدعوم بتقنية DeepSeek AI</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="bg-white rounded-3 p-3 text-center h-100">
                        <i class="fas fa-clock fa-2x text-warning mb-2 d-block"></i>
                        <h6 class="mb-1">إجابات فورية</h6>
                        <p class="small text-muted mb-0">دون الحاجة لتحديث الصفحة</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<footer>
    <div class="container text-center">
        <p class="mb-0">&copy; <?= date('Y') ?> منصة التراث والتاريخ الجزائري</p>
        <p class="small mt-1">المساعد الذكي مدعوم بـ DeepSeek API</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    AOS.init({ duration: 1000, once: true });
    
    // الأسئلة المقترحة
    const suggestedQuestions = [
        "ما هي أقدم الحضارات التي سكنت الجزائر؟",
        "من هم أبرز القادة التاريخيين في الجزائر؟",
        "اشرح لي ثورة التحرير الجزائرية",
        "ما هي أهم المعالم الأثرية في الجزائر؟",
        "ما هي قصة الملكة كاهنة؟",
        "اشرح لي الدولة الزيانية في تلمسان"
    ];
    
    // عرض الأسئلة المقترحة
    const suggestionsContainer = document.getElementById('suggestionsContainer');
    suggestedQuestions.forEach(q => {
        const btn = document.createElement('div');
        btn.className = 'suggestion-btn';
        btn.textContent = q;
        btn.onclick = () => askQuestion(q);
        suggestionsContainer.appendChild(btn);
    });
    
    // عناصر DOM
    const chatMessages = document.getElementById('chatMessages');
    const questionInput = document.getElementById('questionInput');
    const sendBtn = document.getElementById('sendBtn');
    
    // دالة التمرير للأسفل
    function scrollToBottom() {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    
    // دالة إضافة رسالة المستخدم
    function addUserMessage(question) {
        const messageDiv = document.createElement('div');
        messageDiv.className = 'message user';
        messageDiv.innerHTML = `
            <div class="message-avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="message-content">
                ${escapeHtml(question)}
            </div>
        `;
        chatMessages.appendChild(messageDiv);
        scrollToBottom();
    }
    
    // دالة إضافة مؤشر الكتابة
    function addTypingIndicator() {
        const typingDiv = document.createElement('div');
        typingDiv.className = 'message ai';
        typingDiv.id = 'typingIndicator';
        typingDiv.innerHTML = `
            <div class="message-avatar">
                <i class="fas fa-robot"></i>
            </div>
            <div class="typing-indicator">
                <span class="typing-dot"></span>
                <span class="typing-dot"></span>
                <span class="typing-dot"></span>
            </div>
        `;
        chatMessages.appendChild(typingDiv);
        scrollToBottom();
    }
    
    // دالة إزالة مؤشر الكتابة
    function removeTypingIndicator() {
        const indicator = document.getElementById('typingIndicator');
        if (indicator) {
            indicator.remove();
        }
    }
    
    // دالة إضافة رد الذكاء الاصطناعي
    function addAIResponse(response) {
        const messageDiv = document.createElement('div');
        messageDiv.className = 'message ai';
        messageDiv.innerHTML = `
            <div class="message-avatar">
                <i class="fas fa-robot"></i>
            </div>
            <div class="message-content">
                ${formatResponse(response)}
            </div>
        `;
        chatMessages.appendChild(messageDiv);
        scrollToBottom();
    }
    
    // دالة تنسيق النص
    function formatResponse(text) {
        let formatted = text
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            .replace(/\n-/g, '<br>•')
            .replace(/\n\d+\./g, '<br>•')
            .replace(/\n/g, '<br>');
        return formatted;
    }
    
    // دالة تحويل النص إلى HTML آمن
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // دالة إرسال السؤال
    async function askQuestion(question) {
        if (!question.trim()) return;
        
        // إضافة رسالة المستخدم
        addUserMessage(question);
        
        // مسح حقل الإدخال
        questionInput.value = '';
        questionInput.style.height = 'auto';
        
        // تعطيل زر الإرسال
        sendBtn.disabled = true;
        sendBtn.innerHTML = '<i class="fas fa-spinner fa-pulse"></i> جاري...';
        
        // إضافة مؤشر الكتابة
        addTypingIndicator();
        
        try {
            // إرسال الطلب إلى الخادم
            const response = await fetch('ai_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ question: question })
            });
            
            const data = await response.json();
            
            // إزالة مؤشر الكتابة
            removeTypingIndicator();
            
            if (data.success) {
                addAIResponse(data.response);
            } else {
                addAIResponse('عذراً، حدث خطأ: ' + data.message);
            }
        } catch (error) {
            removeTypingIndicator();
            addAIResponse('عذراً، حدث خطأ في الاتصال بالخادم. يرجى المحاولة مرة أخرى.');
        } finally {
            // إعادة تفعيل زر الإرسال
            sendBtn.disabled = false;
            sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i> إرسال';
        }
    }
    
    // حدث النقر على زر الإرسال
    sendBtn.addEventListener('click', () => {
        const question = questionInput.value.trim();
        if (question) {
            askQuestion(question);
        }
    });
    
    // حدث الضغط على Enter
    questionInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            const question = questionInput.value.trim();
            if (question) {
                askQuestion(question);
            }
        }
    });
    
    // تغيير ارتفاع حقل الإدخال تلقائياً
    questionInput.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 100) + 'px';
    });
    
    // التمرير للأسفل عند التحميل
    scrollToBottom();
</script>
</body>
</html>
